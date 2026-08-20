<?php

namespace App\Services\Paiement;

use App\Models\Agent;
use App\Models\Debiteur;
use App\Models\Dette;
use App\Models\Paiement;
use App\Models\Partenaire;
use App\Models\Recu;
use App\Notifications\PaiementConfirme;
use App\Support\Montants;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Cycle de vie d'un paiement, de l'intention à l'imputation comptable.
 *
 * C'est le seul endroit où une dette est créditée. La règle tient en une
 * phrase : rien n'est imputé tant que le prestataire n'a pas confirmé, et une
 * confirmation déjà traitée ne l'est jamais deux fois — un webhook peut arriver
 * après que le tableau de bord a déjà relu le statut.
 */
class ServicePaiement
{
    /** @var FabriquePasserelles */
    protected $passerelles;

    public function __construct(FabriquePasserelles $passerelles)
    {
        $this->passerelles = $passerelles;
    }

    /**
     * Déclare l'intention de paiement et ouvre la transaction chez le prestataire.
     *
     * @param  array  $donnees  montant, moyen, telephone, url_retour.
     * @return Paiement
     */
    public function initier(Debiteur $debiteur, Dette $dette, array $donnees)
    {
        $passerelle = $this->passerelles->pourMoyen($donnees['moyen']);

        $paiement = new Paiement([
            'debiteur_id' => (string) $debiteur->_id,
            'dette_id' => (string) $dette->_id,
            'partenaire_id' => $dette->partenaire_id ? (string) $dette->partenaire_id : null,
            'montant' => Montants::versEntier($donnees['montant']),
            'devise' => config('paiements.devise'),
            'moyen' => $donnees['moyen'],
            'telephone' => isset($donnees['telephone']) ? $donnees['telephone'] : null,
            'passerelle' => $passerelle->nom(),
            'statut' => Paiement::STATUT_INITIE,
        ]);

        // La référence est nécessaire dès l'appel au prestataire, qui s'en sert
        // d'identifiant de transaction : le document est donc écrit avant.
        $paiement->save();

        try {
            $passerelle->initier($paiement, $this->urlRetour($donnees, $paiement));
        } catch (EchecPasserelle $e) {
            $paiement->statut = Paiement::STATUT_ECHOUE;
            $paiement->message_echec = $e->getMessage();
            $paiement->save();

            throw $e;
        }

        $paiement->save();

        return $paiement;
    }

    /**
     * Relit le statut auprès du prestataire et en tire les conséquences.
     *
     * @return Paiement
     */
    public function actualiser(Paiement $paiement)
    {
        if (! $paiement->estEnCours()) {
            return $paiement;
        }

        if ($paiement->estExpire()) {
            return $this->clore($paiement, Paiement::STATUT_ECHOUE, 'Délai de validation dépassé.');
        }

        try {
            $statut = $this->passerelles->parNom($paiement->passerelle)->verifier($paiement);
        } catch (EchecPasserelle $e) {
            // Un prestataire injoignable ne condamne pas le paiement : le débiteur
            // peut avoir validé, la prochaine relecture le dira.
            Log::warning('Statut de paiement indisponible', [
                'reference' => $paiement->reference,
                'erreur' => $e->getMessage(),
            ]);

            return $paiement;
        }

        if ($statut === Paiement::STATUT_REUSSI) {
            return $this->confirmer($paiement);
        }

        if ($statut !== $paiement->statut) {
            $paiement->statut = $statut;
            $paiement->save();
        }

        return $paiement;
    }

    /**
     * Impute le règlement : dette créditée, reçu émis, tiers notifiés.
     *
     * L'ordre compte. La dette est créditée en premier — c'est le seul effet
     * qu'il serait grave de perdre ; un reçu ou un courriel manquant se rattrape,
     * un encaissement non imputé se réclame deux fois.
     *
     * @return Paiement
     */
    public function confirmer(Paiement $paiement)
    {
        if ($paiement->estReussi()) {
            return $paiement;
        }

        $dette = Dette::find($paiement->dette_id);

        if ($dette) {
            $this->crediter($dette, (int) $paiement->montant);
        }

        $paiement->statut = Paiement::STATUT_REUSSI;
        $paiement->confirme_le = Carbon::now();
        $paiement->message_echec = null;
        $paiement->save();

        $recu = $this->emettreRecu($paiement, $dette);
        $paiement->recu_id = $recu ? (string) $recu->_id : null;
        $paiement->notifications = $this->notifier($paiement, $dette);
        $paiement->save();

        return $paiement;
    }

    /**
     * Abandon à la main du débiteur. Sans effet sur un paiement déjà tranché :
     * un encaissement confirmé ne s'annule que par un remboursement.
     *
     * @return Paiement
     */
    public function annuler(Paiement $paiement)
    {
        if (! $paiement->estEnCours()) {
            return $paiement;
        }

        return $this->clore($paiement, Paiement::STATUT_ANNULE, 'Paiement abandonné par le débiteur.');
    }

    /**
     * Crédite la dette du montant encaissé.
     *
     * Les montants sont des chaînes en base, parfois saisies avec des espaces.
     * Le hook `saving` du modèle Dette recalcule le solde par un simple cast
     * `(int)`, qui lirait « 1 000 000 » comme 1 : les deux montants sont donc
     * réécrits normalisés, sans changer leur valeur.
     */
    protected function crediter(Dette $dette, $montant)
    {
        $dette->montant_reconnu = Montants::versEntier($dette->montant_reconnu);
        $dette->montant_verse = Montants::versEntier($dette->montant_verse) + $montant;
        $dette->dernier_versement = Carbon::now()->toDateString();
        $dette->save();
    }

    protected function clore(Paiement $paiement, $statut, $motif)
    {
        $paiement->statut = $statut;
        $paiement->message_echec = $motif;
        $paiement->save();

        return $paiement;
    }

    /**
     * Reçu au format déjà connu de l'administration, consultable dans le
     * back-office au même titre que les versements saisis à la main.
     */
    protected function emettreRecu(Paiement $paiement, Dette $dette = null)
    {
        try {
            return Recu::create([
                'montant' => (int) $paiement->montant,
                'mode' => Paiement::libelleMoyen($paiement->moyen),
                'date' => Carbon::now()->toDateString(),
                'dette_id' => $paiement->dette_id,
                'debiteur_id' => $paiement->debiteur_id,
                'partenaire_id' => $paiement->partenaire_id,
                'paiement_id' => (string) $paiement->_id,
                'commentaire' => 'Paiement en ligne ' . $paiement->reference
                    . ($dette ? ' — ' . $dette->intitule : ''),
            ]);
        } catch (\Exception $e) {
            Log::error("Reçu non émis pour le paiement {$paiement->reference}", [
                'erreur' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Prévient l'administration, le partenaire créancier et l'agent en charge.
     *
     * Chaque envoi est isolé : un partenaire sans adresse valable ne doit pas
     * empêcher l'administration d'être prévenue. Le journal renvoyé au tableau
     * de bord dit au débiteur qui a effectivement été averti.
     *
     * @return array
     */
    protected function notifier(Paiement $paiement, Dette $dette = null)
    {
        $journal = [];
        $reglages = config('paiements.notifications');

        $destinataires = [];

        if (! empty($reglages['admin'])) {
            $destinataires[] = ['admin', $reglages['admin'], 'Administration ' . config('app.name')];
        }

        if (! empty($reglages['partenaire']) && $paiement->partenaire_id) {
            $partenaire = Partenaire::find($paiement->partenaire_id);

            if ($partenaire && $partenaire->email) {
                $destinataires[] = ['partenaire', $partenaire->email, $partenaire->nom];
            }
        }

        $debiteur = Debiteur::find($paiement->debiteur_id);

        if (! empty($reglages['agent']) && $debiteur && $debiteur->agent_id) {
            $agent = Agent::find((string) $debiteur->agent_id);

            if ($agent && $agent->email) {
                $destinataires[] = ['agent', $agent->email, trim($agent->prenom . ' ' . $agent->nom)];
            }
        }

        foreach ($destinataires as list($role, $adresse, $nom)) {
            $envoyee = true;

            try {
                Notification::route('mail', $adresse)
                    ->notify(new PaiementConfirme($paiement, $dette, $debiteur, $role));
            } catch (\Exception $e) {
                $envoyee = false;

                Log::error("Notification de paiement non envoyée à {$adresse}", [
                    'reference' => $paiement->reference,
                    'erreur' => $e->getMessage(),
                ]);
            }

            $journal[] = [
                'destinataire' => $role,
                'canal' => 'email',
                'nom' => $nom,
                'envoyee' => $envoyee,
            ];
        }

        return $journal;
    }

    /**
     * Adresse de retour transmise au prestataire, complétée de la référence du
     * paiement : c'est elle que le tableau de bord relit pour reprendre le suivi.
     */
    protected function urlRetour(array $donnees, Paiement $paiement)
    {
        $base = ! empty($donnees['url_retour'])
            ? $donnees['url_retour']
            : config('app.url') . '/debiteur/paiements';

        $separateur = strpos($base, '?') === false ? '?' : '&';

        return $base . $separateur . 'paiement=' . $paiement->_id;
    }
}

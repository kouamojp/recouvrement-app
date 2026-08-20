<?php

namespace App\Services\Paiement;

use App\Models\Paiement;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * CinetPay — carte bancaire, Orange Money et MTN Mobile Money.
 *
 * Un seul compte marchand couvre les trois moyens en zone CEMAC. Le débiteur
 * est envoyé sur la page de paiement hébergée par CinetPay : aucune donnée de
 * carte ni code opérateur ne transite par l'application.
 */
class PasserelleCinetPay implements Passerelle
{
    /** @var Client */
    protected $http;

    /** @var array */
    protected $config;

    public function __construct(Client $http, array $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    public function nom()
    {
        return 'cinetpay';
    }

    public function initier(Paiement $paiement, $urlRetour)
    {
        $reponse = $this->appeler('/payment', [
            'transaction_id' => $paiement->reference,
            'amount' => $this->montantOperateur($paiement),
            'currency' => $this->config['devise'],
            'description' => 'Reglement dette ' . $paiement->reference,
            'channels' => $this->canal($paiement->moyen),
            'customer_phone_number' => $paiement->telephone,
            'return_url' => $urlRetour,
            'notify_url' => route('paiements.webhook', ['passerelle' => $this->nom()]),
            'metadata' => $paiement->dette_id,
        ]);

        $donnees = isset($reponse['data']) ? $reponse['data'] : [];

        if (empty($donnees['payment_url'])) {
            throw new EchecPasserelle("CinetPay n'a pas renvoyé d'URL de paiement.");
        }

        $paiement->reference_externe = $paiement->reference;
        $paiement->url_redirection = $donnees['payment_url'];
        $paiement->statut = Paiement::STATUT_EN_ATTENTE;
        $paiement->payload = $reponse;

        // Le mobile money passe malgré tout par la page CinetPay, qui déclenche
        // la demande de validation sur le téléphone : la consigne prépare le
        // débiteur à ce qui va se passer sur son combiné.
        if (in_array($paiement->moyen, Paiement::MOYENS_MOBILES, true)) {
            $paiement->instruction = 'Validez la demande de paiement reçue sur le '
                . $paiement->telephone . ', puis patientez sur cette page.';
        }
    }

    public function verifier(Paiement $paiement)
    {
        $reponse = $this->appeler('/payment/check', [
            'transaction_id' => $paiement->reference_externe ?: $paiement->reference,
        ]);

        $donnees = isset($reponse['data']) ? $reponse['data'] : [];
        $code = isset($donnees['status']) ? $donnees['status'] : null;

        // « ACCEPTED » est le seul statut valant encaissement ; « PENDING »
        // couvre l'attente de validation sur le téléphone.
        if ($code === 'ACCEPTED') {
            return Paiement::STATUT_REUSSI;
        }

        if ($code === 'PENDING' || $code === 'WAITING_FOR_CUSTOMER') {
            return Paiement::STATUT_EN_ATTENTE;
        }

        if ($code === 'REFUSED' || $code === 'CANCELED') {
            $paiement->message_echec = isset($donnees['description'])
                ? $donnees['description']
                : 'Paiement refusé par CinetPay.';

            return $code === 'CANCELED' ? Paiement::STATUT_ANNULE : Paiement::STATUT_ECHOUE;
        }

        return Paiement::STATUT_EN_ATTENTE;
    }

    public function referenceDepuisWebhook(array $charge)
    {
        return isset($charge['cpm_trans_id']) ? $charge['cpm_trans_id'] : null;
    }

    /**
     * CinetPay règle en XAF, par multiples de 5 imposés par les opérateurs.
     *
     * Le montant retenu est arrondi vers le bas : jamais débiter le débiteur de
     * plus que ce qu'il a validé. Le reliquat de quelques francs demeure au
     * solde de la dette.
     */
    protected function montantOperateur(Paiement $paiement)
    {
        return (int) (floor((int) $paiement->montant / 5) * 5);
    }

    protected function canal($moyen)
    {
        $canaux = $this->config['canaux'];

        return isset($canaux[$moyen]) ? $canaux[$moyen] : 'ALL';
    }

    protected function appeler($chemin, array $corps)
    {
        $corps['apikey'] = $this->config['api_key'];
        $corps['site_id'] = $this->config['site_id'];

        try {
            $reponse = $this->http->post(rtrim($this->config['url'], '/') . $chemin, [
                'json' => $corps,
                'timeout' => 20,
            ]);
        } catch (GuzzleException $e) {
            throw new EchecPasserelle('CinetPay est injoignable : ' . $e->getMessage(), false, $e);
        }

        $decodee = json_decode((string) $reponse->getBody(), true);

        if (! is_array($decodee)) {
            throw new EchecPasserelle('Réponse illisible de CinetPay.');
        }

        // Le code 201 signale une transaction déjà connue : ce n'est pas une
        // erreur lors d'une relecture de statut.
        $code = isset($decodee['code']) ? (string) $decodee['code'] : '';

        if ($code !== '' && ! in_array($code, ['201', '00'], true)) {
            throw new EchecPasserelle(
                isset($decodee['message']) ? $decodee['message'] : 'CinetPay a refusé la requête.',
                true
            );
        }

        return $decodee;
    }
}

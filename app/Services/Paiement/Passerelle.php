<?php

namespace App\Services\Paiement;

use App\Models\Paiement;

/**
 * Contrat commun aux prestataires de paiement.
 *
 * Une passerelle ne touche jamais aux dettes ni aux notifications : elle sait
 * seulement ouvrir une transaction chez son prestataire et dire où elle en est.
 * L'imputation comptable est du ressort de {@see ServicePaiement}.
 */
interface Passerelle
{
    /** Identifiant de la passerelle, tel qu'il apparaît en configuration. */
    public function nom();

    /**
     * Ouvre la transaction chez le prestataire.
     *
     * Renseigne sur le paiement, selon le prestataire : `url_redirection` pour
     * une page tierce, `instruction` pour une validation sur téléphone, ainsi
     * que `reference_externe` et `statut`. Le paiement n'est pas enregistré ici.
     *
     * @param  string  $urlRetour  Adresse à laquelle renvoyer le débiteur.
     * @throws EchecPasserelle
     */
    public function initier(Paiement $paiement, $urlRetour);

    /**
     * Relit le statut auprès du prestataire.
     *
     * @return string  Une des constantes STATUT_* de {@see Paiement}.
     * @throws EchecPasserelle
     */
    public function verifier(Paiement $paiement);

    /**
     * Extrait d'une notification serveur à serveur la référence concernée.
     *
     * Le webhook n'est pas cru sur parole : il ne sert qu'à savoir qu'il faut
     * relire le statut, que `verifier()` ira chercher à la source.
     *
     * @return string|null  Référence externe, ou null si la charge est illisible.
     */
    public function referenceDepuisWebhook(array $charge);
}

<?php

namespace App\Services\Paiement;

use App\Models\Paiement;

/**
 * Passerelle de développement, sans prestataire réel.
 *
 * Elle permet de dérouler tout le tunnel — attente, confirmation, mise à jour
 * de la dette, notifications — sans compte marchand. Le paiement bascule en
 * réussi au bout de quelques secondes, le temps que le tableau de bord affiche
 * son écran d'attente. C'est le repli automatique lorsqu'une passerelle réelle
 * n'a pas ses identifiants ; elle refuse de servir en production.
 */
class PasserelleFactice implements Passerelle
{
    /** Délai avant confirmation automatique, en secondes. */
    const DELAI_CONFIRMATION = 8;

    public function nom()
    {
        return 'factice';
    }

    public function initier(Paiement $paiement, $urlRetour)
    {
        $paiement->reference_externe = 'FACTICE-' . $paiement->reference;
        $paiement->statut = Paiement::STATUT_EN_ATTENTE;
        $paiement->instruction = 'Paiement simulé : aucun prestataire réel '
            . "n'est configuré. La confirmation arrive dans quelques secondes.";

        // Aucune redirection : le débiteur reste sur l'écran d'attente, que le
        // suivi de statut fera basculer tout seul.
        $paiement->url_redirection = null;
    }

    public function verifier(Paiement $paiement)
    {
        if (! $paiement->created_at) {
            return Paiement::STATUT_EN_ATTENTE;
        }

        return $paiement->created_at->addSeconds(self::DELAI_CONFIRMATION)->isPast()
            ? Paiement::STATUT_REUSSI
            : Paiement::STATUT_EN_ATTENTE;
    }

    public function referenceDepuisWebhook(array $charge)
    {
        return isset($charge['reference']) ? $charge['reference'] : null;
    }
}

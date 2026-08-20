<?php

namespace App\Services\Paiement;

use RuntimeException;

/**
 * Le prestataire a refusé la transaction ou n'a pas répondu.
 *
 * Distingue un incident technique — à remonter au débiteur en « réessayez » —
 * d'un refus métier, qui donne lieu à un paiement en statut `echoue`.
 */
class EchecPasserelle extends RuntimeException
{
    /** @var bool Le refus vient du prestataire, pas du réseau. */
    protected $definitif;

    public function __construct($message, $definitif = false, $precedente = null)
    {
        parent::__construct($message, 0, $precedente);

        $this->definitif = $definitif;
    }

    public function estDefinitif()
    {
        return $this->definitif;
    }
}

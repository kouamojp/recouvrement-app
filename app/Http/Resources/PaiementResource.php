<?php

namespace App\Http\Resources;

use App\Support\Montants;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ni la charge utile brute du prestataire ni le numéro de téléphone débité ne
 * sortent d'ici : ils n'ont rien à faire dans un navigateur.
 */
class PaiementResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => (string) $this->_id,
            'reference'       => $this->reference,
            'dette_id'        => $this->dette_id ? (string) $this->dette_id : null,
            'partenaire_id'   => $this->partenaire_id ? (string) $this->partenaire_id : null,
            'montant'         => Montants::versEntier($this->montant),
            'devise'          => $this->devise ?: Montants::DEVISE,
            'moyen'           => $this->moyen,
            'statut'          => $this->statut,
            // Page du prestataire pour la carte et PayPal, consigne de
            // validation pour le mobile money : jamais les deux à la fois.
            'url_redirection' => $this->url_redirection ?: null,
            'instruction'     => $this->instruction ?: null,
            'message_echec'   => $this->message_echec ?: null,
            'notifications'   => $this->notifications ?: [],
            'cree_le'         => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'confirme_le'     => $this->confirme_le ? $this->confirme_le->toDateTimeString() : null,
        ];
    }
}

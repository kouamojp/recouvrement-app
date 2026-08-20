<?php

namespace App\Http\Resources;

use App\Support\Montants;
use Illuminate\Http\Resources\Json\JsonResource;

class RecuResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => (string) $this->_id,
            'bordereau'     => $this->bordereau,
            'montant'       => Montants::versEntier($this->montant),
            'devise'        => Montants::DEVISE,
            // Libellé libre : saisi à la main dans le back-office (« Chèque »,
            // « MTN MoMo »), ou posé par le tunnel de paiement en ligne.
            'mode'          => $this->mode ?: null,
            'date'          => $this->date ?: null,
            'dette_id'      => $this->dette_id ? (string) $this->dette_id : null,
            'partenaire_id' => $this->partenaire_id ? (string) $this->partenaire_id : null,
            // Renseigné uniquement pour les reçus issus d'un paiement en ligne.
            'paiement_id'   => $this->paiement_id ? (string) $this->paiement_id : null,
            'commentaire'   => $this->commentaire ?: null,
            'cree_le'       => optional($this->created_at)->toIso8601String(),
        ];
    }
}

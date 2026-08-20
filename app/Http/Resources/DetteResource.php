<?php

namespace App\Http\Resources;

use App\Support\Montants;
use Illuminate\Http\Resources\Json\JsonResource;

class DetteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                      => (string) $this->_id,
            'intitule'                => $this->intitule,
            // Les montants sont des chaînes en base : on les normalise en entiers.
            'montant_reclame'         => Montants::versEntier($this->montant_reclame),
            'montant_reconnu'         => Montants::versEntier($this->montant_reconnu),
            'montant_verse'           => Montants::versEntier($this->montant_verse),
            'solde'                   => Montants::solde($this->resource),
            'dernier_versement'       => $this->dernier_versement ?: null,
            'date_echeance_mensuelle' => $this->date_echeance_mensuelle ?: null,
            'debiteur_id'             => $this->debiteur_id ? (string) $this->debiteur_id : null,
            'partenaire_id'           => $this->partenaire_id ? (string) $this->partenaire_id : null,
        ];
    }
}

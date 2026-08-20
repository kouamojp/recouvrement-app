<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DebiteurResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                => (string) $this->_id,
            'societe_debitrice' => $this->societe_debitrice,
            'gerant'            => $this->gerant,
            'ville'             => $this->ville,
            'localisation'      => $this->localisation,
            'telephone'         => $this->telephone,
            'email'             => $this->email,
            'agent_id'          => $this->agent_id ? (string) $this->agent_id : null,
            'partenaires_ids'   => array_map('strval', (array) ($this->partenaires ?? [])),
            'cree_le'           => optional($this->created_at)->toIso8601String(),
            'modifie_le'        => optional($this->updated_at)->toIso8601String(),
        ];
    }
}

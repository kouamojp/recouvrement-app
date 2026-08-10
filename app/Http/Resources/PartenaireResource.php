<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PartenaireResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => (string) $this->_id,
            'nom'        => $this->nom,
            'adresse'    => $this->adresse,
            'ville'      => $this->ville,
            'telephone'  => $this->telephone,
            'email'      => $this->email,
            'secteur'    => $this->secteur,
            'cree_le'    => optional($this->created_at)->toIso8601String(),
            'modifie_le' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}

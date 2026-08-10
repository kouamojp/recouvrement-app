<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => (string) $this->_id,
            'nom'        => $this->nom,
            'prenom'     => $this->prenom,
            'email'      => $this->email,
            'telephone'  => $this->telephone,
            'cree_le'    => optional($this->created_at)->toIso8601String(),
            'modifie_le' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}

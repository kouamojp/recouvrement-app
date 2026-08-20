<?php

namespace App\Http\Resources;

use App\Support\Montants;
use Illuminate\Http\Resources\Json\JsonResource;

class RapportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                     => (string) $this->_id,
            'partenaire_id'          => $this->partenaire_id ? (string) $this->partenaire_id : null,
            'montant_creance'        => Montants::versEntier($this->montant_creance),
            'nbre_dossier_transmi'   => Montants::versEntier($this->nbre_dossier_transmi),
            'nbre_dossier_actif'     => Montants::versEntier($this->nbre_dossier_actif),
            'nbre_dossier_localiser' => Montants::versEntier($this->nbre_dossier_localiser),
            'nbre_dossier_payement'  => Montants::versEntier($this->nbre_dossier_payement),
            'entr_physiq'            => Montants::versEntier($this->entr_physiq),
            'trans_courier'          => Montants::versEntier($this->trans_courier),
            'negoc_en_cours'         => Montants::versEntier($this->negoc_en_cours),
            'protocol_signe'         => Montants::versEntier($this->protocol_signe),
            'echange_tel'            => Montants::versEntier($this->echange_tel),
            'echange_email'          => Montants::versEntier($this->echange_email),
            'commentaire'            => $this->commentaire ?: null,
            'devise'                 => Montants::DEVISE,
            'cree_le'                => optional($this->created_at)->toIso8601String(),
            'modifie_le'             => optional($this->updated_at)->toIso8601String(),
        ];
    }
}

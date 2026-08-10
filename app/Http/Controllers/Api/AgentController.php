<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Http\Resources\DebiteurResource;
use App\Http\Resources\DetteResource;
use App\Models\Debiteur;
use App\Models\Dette;
use App\Support\Montants;

/**
 * Espace personnel de l'agent de recouvrement connecté.
 *
 * Le périmètre d'un agent est l'ensemble des débiteurs qui lui sont assignés
 * via le champ `agent_id`, et les dettes de ces débiteurs.
 */
class AgentController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.profil:agent');
    }

    /**
     * GET /api/agent/me
     */
    public function me()
    {
        return new AgentResource(auth()->user());
    }

    /**
     * GET /api/agent/me/debiteurs
     */
    public function debiteurs()
    {
        return DebiteurResource::collection($this->debiteursSuivis());
    }

    /**
     * GET /api/agent/me/dettes
     */
    public function dettes()
    {
        return DetteResource::collection($this->dettesSuivies());
    }

    /**
     * GET /api/agent/me/synthese
     */
    public function synthese()
    {
        $synthese = Montants::synthese($this->dettesSuivies());
        $synthese['nombre_debiteurs'] = $this->debiteursSuivis()->count();

        return response()->json($synthese);
    }

    /**
     * Débiteurs assignés à l'agent connecté.
     */
    protected function debiteursSuivis()
    {
        return Debiteur::where('agent_id', (string) auth()->user()->_id)->get();
    }

    /**
     * Dettes de tous les débiteurs suivis.
     */
    protected function dettesSuivies()
    {
        $ids = $this->debiteursSuivis()
            ->map(function ($debiteur) {
                return (string) $debiteur->_id;
            })
            ->all();

        if (empty($ids)) {
            return collect();
        }

        return Dette::whereIn('debiteur_id', $ids)->get();
    }
}

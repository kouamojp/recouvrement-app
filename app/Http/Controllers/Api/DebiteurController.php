<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Http\Resources\DebiteurResource;
use App\Http\Resources\DetteResource;
use App\Http\Resources\PartenaireResource;
use App\Http\Resources\RecuResource;
use App\Models\Agent;
use App\Models\Dette;
use App\Models\Partenaire;
use App\Models\Recu;
use App\Support\Montants;

/**
 * Espace personnel du débiteur connecté.
 *
 * Aucune méthode n'accepte d'identifiant en paramètre : tout est dérivé du
 * token, un débiteur ne peut donc jamais consulter le dossier d'un autre.
 */
class DebiteurController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.profil:debiteur');
    }

    /**
     * GET /api/debiteur/me
     */
    public function me()
    {
        return new DebiteurResource(auth()->user());
    }

    /**
     * GET /api/debiteur/me/dettes
     */
    public function dettes()
    {
        return DetteResource::collection($this->dettesDuDebiteur());
    }

    /**
     * GET /api/debiteur/me/partenaires
     */
    public function partenaires()
    {
        $debiteur = auth()->user();

        // Deux sources se complètent : le rattachement explicite saisi dans
        // l'admin (tableau `partenaires`) et les partenaires porteurs des
        // dettes du débiteur.
        $ids = array_map('strval', (array) ($debiteur->partenaires ?? []));

        foreach ($this->dettesDuDebiteur() as $dette) {
            if ($dette->partenaire_id) {
                $ids[] = (string) $dette->partenaire_id;
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));

        $partenaires = empty($ids)
            ? collect()
            : Partenaire::whereIn('_id', $ids)->get();

        return PartenaireResource::collection($partenaires);
    }

    /**
     * GET /api/debiteur/me/agent
     *
     * Répond 204 sans corps quand aucun agent n'est assigné : c'est un cas
     * normal, pas une erreur, et le HttpClient d'Angular le restitue en null.
     */
    public function agent()
    {
        $debiteur = auth()->user();

        $agent = $debiteur->agent_id
            ? Agent::find((string) $debiteur->agent_id)
            : null;

        return $agent ? new AgentResource($agent) : response()->noContent();
    }

    /**
     * GET /api/debiteur/me/recus
     *
     * Reçus de versement, du plus récent au plus ancien. Deux origines s'y
     * mêlent : les règlements encaissés en ligne et ceux saisis à la main dans
     * le back-office. Le débiteur n'a pas à faire la différence.
     */
    public function recus()
    {
        $recus = Recu::where('debiteur_id', (string) auth()->user()->_id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return RecuResource::collection($recus);
    }

    /**
     * GET /api/debiteur/me/synthese
     */
    public function synthese()
    {
        return response()->json(Montants::synthese($this->dettesDuDebiteur()));
    }

    /**
     * Dettes rattachées au débiteur connecté.
     */
    protected function dettesDuDebiteur()
    {
        return Dette::where('debiteur_id', (string) auth()->user()->_id)->get();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DebiteurResource;
use App\Http\Resources\DetteResource;
use App\Http\Resources\PartenaireResource;
use App\Http\Resources\RapportResource;
use App\Models\Debiteur;
use App\Models\Dette;
use App\Models\Rapport;
use App\Support\Montants;

/**
 * Espace personnel du partenaire connecté.
 *
 * Comme pour les débiteurs, le périmètre est intégralement dérivé du token.
 */
class PartenaireController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.profil:partenaire');
    }

    /**
     * GET /api/partenaire/me
     */
    public function me()
    {
        return new PartenaireResource(auth()->user());
    }

    /**
     * GET /api/partenaire/me/dettes
     */
    public function dettes()
    {
        return DetteResource::collection($this->dettesDuPartenaire());
    }

    /**
     * GET /api/partenaire/me/debiteurs
     */
    public function debiteurs()
    {
        return DebiteurResource::collection($this->debiteursDuPartenaire());
    }

    /**
     * GET /api/partenaire/me/rapport
     *
     * Répond 204 sans corps tant qu'aucun rapport n'a été produit pour ce
     * partenaire, ce que le HttpClient d'Angular restitue en null.
     */
    public function rapport()
    {
        $rapport = Rapport::where('partenaire_id', $this->identifiant())->first();

        return $rapport ? new RapportResource($rapport) : response()->noContent();
    }

    /**
     * GET /api/partenaire/me/synthese
     */
    public function synthese()
    {
        $synthese = Montants::synthese($this->dettesDuPartenaire());
        $synthese['nombre_debiteurs'] = $this->debiteursDuPartenaire()->count();

        return response()->json($synthese);
    }

    protected function identifiant()
    {
        return (string) auth()->user()->_id;
    }

    /**
     * Dettes portées par le partenaire connecté.
     */
    protected function dettesDuPartenaire()
    {
        return Dette::where('partenaire_id', $this->identifiant())->get();
    }

    /**
     * Débiteurs du partenaire : ceux qui le référencent explicitement dans leur
     * tableau `partenaires`, et ceux dont il porte au moins une dette.
     */
    protected function debiteursDuPartenaire()
    {
        $id = $this->identifiant();

        $idsParDettes = $this->dettesDuPartenaire()
            ->pluck('debiteur_id')
            ->filter()
            ->map(function ($valeur) {
                return (string) $valeur;
            })
            ->unique()
            ->values()
            ->all();

        return Debiteur::where(function ($requete) use ($id, $idsParDettes) {
            // Sur MongoDB, une égalité sur un champ tableau teste l'appartenance.
            $requete->where('partenaires', $id);

            if (! empty($idsParDettes)) {
                $requete->orWhereIn('_id', $idsParDettes);
            }
        })->get();
    }
}

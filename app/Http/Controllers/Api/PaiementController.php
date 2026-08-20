<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaiementResource;
use App\Models\Dette;
use App\Models\Paiement;
use App\Services\Paiement\EchecPasserelle;
use App\Services\Paiement\FabriquePasserelles;
use App\Services\Paiement\ServicePaiement;
use App\Support\Montants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Paiements du débiteur connecté.
 *
 * Comme le reste de l'espace débiteur, aucune méthode n'accepte d'identifiant
 * de débiteur : tout est dérivé du token. Une dette qui n'est pas la sienne est
 * introuvable plutôt que refusée — inutile de lui confirmer l'existence du
 * dossier d'un autre.
 */
class PaiementController extends Controller
{
    /** @var ServicePaiement */
    protected $service;

    public function __construct(ServicePaiement $service)
    {
        $this->service = $service;

        // Le webhook est appelé par le prestataire, qui n'a évidemment pas de
        // token : il est authentifié par la relecture du statut à la source.
        $this->middleware('jwt.profil:debiteur')->except('webhook');
    }

    /**
     * GET /api/debiteur/me/paiements
     */
    public function index()
    {
        $paiements = Paiement::duDebiteur(auth()->user()->_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return PaiementResource::collection($paiements);
    }

    /**
     * POST /api/debiteur/me/paiements
     */
    public function store(Request $request)
    {
        $debiteur = auth()->user();

        $donnees = $request->validate([
            'dette_id'   => 'required|string',
            'montant'    => 'required|integer|min:1',
            'moyen'      => ['required', Rule::in(Paiement::MOYENS)],
            'telephone'  => 'required_if:moyen,' . implode(',', Paiement::MOYENS_MOBILES) . '|nullable|string|max:20',
            'url_retour' => 'nullable|url',
        ]);

        $dette = Dette::where('_id', $donnees['dette_id'])
            ->where('debiteur_id', (string) $debiteur->_id)
            ->first();

        if (! $dette) {
            return response()->json(['message' => 'Dette introuvable.'], 404);
        }

        $solde = Montants::solde($dette);

        if ($solde <= 0) {
            return response()->json([
                'message' => 'Cette dette est déjà soldée.',
                'errors'  => ['dette_id' => ['Cette dette est déjà soldée.']],
            ], 422);
        }

        // Le solde est la seule borne qui vaille : payer deux fois n'a pas de
        // sens, et un trop-perçu se rembourse mal.
        if ((int) $donnees['montant'] > $solde) {
            return response()->json([
                'message' => 'Le montant dépasse le solde de la dette.',
                'errors'  => [
                    'montant' => ['Le montant ne peut pas dépasser ' . $solde . ' ' . Montants::DEVISE . '.'],
                ],
            ], 422);
        }

        try {
            $paiement = $this->service->initier($debiteur, $dette, $donnees);
        } catch (EchecPasserelle $e) {
            return response()->json([
                'message' => $e->estDefinitif()
                    ? $e->getMessage()
                    : 'Le service de paiement est momentanément indisponible. Réessayez dans un instant.',
            ], 502);
        }

        return (new PaiementResource($paiement))->response()->setStatusCode(201);
    }

    /**
     * GET /api/debiteur/me/paiements/{id}
     */
    public function show($id)
    {
        $paiement = $this->sien($id);

        if (! $paiement) {
            return response()->json(['message' => 'Paiement introuvable.'], 404);
        }

        // Chaque consultation vaut relecture auprès du prestataire : c'est ce
        // qui fait basculer l'écran d'attente du tableau de bord.
        return new PaiementResource($this->service->actualiser($paiement));
    }

    /**
     * POST /api/debiteur/me/paiements/{id}/annuler
     */
    public function annuler($id)
    {
        $paiement = $this->sien($id);

        if (! $paiement) {
            return response()->json(['message' => 'Paiement introuvable.'], 404);
        }

        return new PaiementResource($this->service->annuler($paiement));
    }

    /**
     * POST /api/paiements/webhook/{passerelle}
     *
     * Route publique, appelée par le prestataire. La charge utile n'est pas
     * crue sur parole : elle sert seulement à repérer la transaction, dont le
     * statut est ensuite relu à la source. Un 200 est toujours renvoyé — un
     * échec ferait rejouer l'appel en boucle chez le prestataire.
     */
    public function webhook(Request $request, FabriquePasserelles $fabrique, $passerelle)
    {
        try {
            $reference = $fabrique->parNom($passerelle)->referenceDepuisWebhook($request->all());

            if ($reference) {
                $paiement = Paiement::where('reference_externe', $reference)
                    ->orWhere('reference', $reference)
                    ->first();

                if ($paiement) {
                    $this->service->actualiser($paiement);
                }
            }
        } catch (\Exception $e) {
            Log::error('Webhook de paiement non traité', [
                'passerelle' => $passerelle,
                'erreur'     => $e->getMessage(),
            ]);
        }

        return response()->json(['recu' => true]);
    }

    /**
     * Paiement appartenant au débiteur connecté, ou null.
     */
    protected function sien($id)
    {
        return Paiement::duDebiteur(auth()->user()->_id)
            ->where('_id', $id)
            ->first();
    }
}

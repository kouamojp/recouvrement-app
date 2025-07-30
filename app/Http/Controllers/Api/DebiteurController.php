<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Debiteur;
use App\Models\Dette;

class DebiteurController extends Controller
{
    public function index()
    {
        return response()->json(Debiteur::all());
    }

    public function show($id)
    {
        $debiteur = Debiteur::find($id);

        if (!$debiteur) {
            return response()->json(['message' => 'Debiteur non trouvé'], 404);
        }

        return response()->json($debiteur);
    }


    public function showDebts($id){


        $debiteur = Debiteur::with('dettes')->find($id);
        if (!$debiteur) {
            return response()->json(['message' => 'Aucune dettes'], 404);
        }
    
        return response()->json($debiteur->dettes);

    }


    public function showPartenaires($id){

        $debiteur = Debiteur::with('partenaires')->find($id);
        if (!$debiteur) {
            return response()->json(['message' => 'Aucun partenaire trouvé'], 404);
        }
    
        return response()->json($debiteur->partenaires);


    }

}
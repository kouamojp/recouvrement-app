<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route de test pour diagnostiquer le problème
Route::get('/test-debiteur', function () {
    try {
        $partenaires = \App\Models\Partenaire::all()->pluck('nom', '_id')->toArray();
        $agents = \App\Models\Agent::all()->pluck('nom', '_id')->toArray();

        return response()->json([
            'status' => 'OK',
            'partenaires_count' => count($partenaires),
            'agents_count' => count($agents),
            'partenaires' => $partenaires,
            'agents' => $agents
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ], 500);
    }
});

// Route de test pour le contrôleur complet
Route::get('/test-debiteur-controller', function () {
    try {
        $controller = new \App\Http\Controllers\Admin\DebiteurCrudController();

        // Simuler le setup
        $controller->setup();

        ob_start();
        $controller->setupCreateOperation();
        $output = ob_get_clean();

        return response()->json([
            'status' => 'OK',
            'message' => 'Le contrôleur fonctionne',
            'output' => $output
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'ERROR',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ], 500);
    } catch (\Error $e) {
        return response()->json([
            'status' => 'FATAL_ERROR',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ], 500);
    }
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Authentification des profils métier
|--------------------------------------------------------------------------
|
| /auth/login est public ; les autres routes exigent un token valide, le
| middleware étant appliqué dans le constructeur de l'AuthController.
|
*/
Route::prefix('auth')->group(function () {
    Route::post('login', 'Api\AuthController@login');
    Route::get('me', 'Api\AuthController@me');
    Route::post('refresh', 'Api\AuthController@refresh');
    Route::post('logout', 'Api\AuthController@logout');
});

/*
|--------------------------------------------------------------------------
| Routes métier
|--------------------------------------------------------------------------
|
| Chaque profil ne voit que son propre périmètre : aucune de ces routes ne
| prend d'identifiant en paramètre, tout est dérivé du token. La restriction
| de profil est déclarée dans le constructeur de chaque contrôleur.
|
*/
Route::prefix('debiteur/me')->group(function () {
    Route::get('/', 'Api\DebiteurController@me');
    Route::get('dettes', 'Api\DebiteurController@dettes');
    Route::get('partenaires', 'Api\DebiteurController@partenaires');
    Route::get('agent', 'Api\DebiteurController@agent');
    Route::get('synthese', 'Api\DebiteurController@synthese');
    Route::get('recus', 'Api\DebiteurController@recus');

    // Règlement en ligne d'une dette.
    Route::get('paiements', 'Api\PaiementController@index');
    Route::post('paiements', 'Api\PaiementController@store');
    Route::get('paiements/{id}', 'Api\PaiementController@show');
    Route::post('paiements/{id}/annuler', 'Api\PaiementController@annuler');
});

Route::prefix('partenaire/me')->group(function () {
    Route::get('/', 'Api\PartenaireController@me');
    Route::get('dettes', 'Api\PartenaireController@dettes');
    Route::get('debiteurs', 'Api\PartenaireController@debiteurs');
    Route::get('rapport', 'Api\PartenaireController@rapport');
    Route::get('synthese', 'Api\PartenaireController@synthese');
});

Route::prefix('agent/me')->group(function () {
    Route::get('/', 'Api\AgentController@me');
    Route::get('debiteurs', 'Api\AgentController@debiteurs');
    Route::get('dettes', 'Api\AgentController@dettes');
    Route::get('synthese', 'Api\AgentController@synthese');
});

/*
|--------------------------------------------------------------------------
| Notification serveur à serveur des prestataires de paiement
|--------------------------------------------------------------------------
|
| Publique par nature : le prestataire n'a pas de token. L'appel est
| authentifié par la relecture du statut à la source, pas par la charge utile
| reçue, qui sert seulement à repérer la transaction concernée.
|
*/
Route::post('paiements/webhook/{passerelle}', 'Api\PaiementController@webhook')
    ->name('paiements.webhook');
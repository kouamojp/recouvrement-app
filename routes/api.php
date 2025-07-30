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

Route::get('/debiteurs', 'Api\DebiteurController@index');
Route::get('/debiteur/{id}', 'Api\DebiteurController@show');
Route::get('/debiteur/{id}/dettes', 'Api\DebiteurController@showDebts');
Route::get('/debiteur/{id}/partenaires', 'Api\DebiteurController@showPartenaires');
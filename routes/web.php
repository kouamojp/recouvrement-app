<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Le back-office vit sous /admin, monté par Backpack depuis
| routes/backpack/. Ne déclarez pas de route en closure ici : `artisan
| route:cache`, employé au déploiement, refuse de sérialiser une closure et
| fait échouer la mise en production.
|
*/

Route::view('/', 'welcome');

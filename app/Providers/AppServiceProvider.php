<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Laravel 7 force error_reporting(-1) et convertit toute notice en
        // ErrorException : une simple depreciation devient donc un 500.
        // ext-mongodb >= 1.20 deprecie `new UTCDateTime($chaine)`, que
        // mongodb/laravel-mongodb v3.7 emet a chaque ecriture de date
        // (Eloquent\Model:92, Query\Builder:933). La bibliotheque n'a pas de
        // version corrigee compatible Laravel 7 : on neutralise le niveau
        // E_DEPRECATED plutot que de patcher vendor/, qui serait ecrase au
        // prochain composer install.
        error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Les collections sont renvoyées sous forme de tableau JSON nu, sans
        // enveloppe `data`, pour que le typage côté Angular reste direct.
        JsonResource::withoutWrapping();
    }
}

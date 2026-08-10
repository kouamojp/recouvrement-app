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
        //
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

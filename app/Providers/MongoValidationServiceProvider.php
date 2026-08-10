<?php

namespace App\Providers;

use App\Validation\MongoPresenceVerifier;
use Jenssegers\Mongodb\Validation\ValidationServiceProvider as MongoBaseProvider;

/**
 * Remplace Jenssegers\Mongodb\Validation\ValidationServiceProvider dans
 * config/app.php : même rôle (faire pointer unique/exists sur Mongo), mais
 * avec un vérificateur ancré qui ne confond plus "a@b.com" et "aa@b.com".
 *
 * Le provider est deferred, d'où la sous-classe : un binding posé depuis
 * AppServiceProvider serait écrasé au moment de la résolution.
 */
class MongoValidationServiceProvider extends MongoBaseProvider
{
    protected function registerPresenceVerifier()
    {
        $this->app->singleton('validation.presence', function ($app) {
            return new MongoPresenceVerifier($app['db']);
        });
    }
}

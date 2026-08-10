<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Force Laravel à répondre en JSON sur toutes les routes d'API.
 *
 * Sans cela, une erreur de validation renvoie une redirection 302 plutôt qu'un
 * 422 JSON dès que le client n'envoie pas d'en-tête `Accept: application/json`
 * — ce que le HttpClient d'Angular ne fait pas spontanément. Le même mécanisme
 * couvre les 401, 403 et 404 générés par le framework.
 */
class ForceReponseJson
{
    public function handle($request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}

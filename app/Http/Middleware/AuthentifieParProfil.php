<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\AuthController;
use Closure;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Authentifie la requête à partir du claim `profil` porté par le token, puis
 * active le guard correspondant pour la suite du traitement.
 *
 * Sans argument, le middleware accepte n'importe quel profil valide :
 *
 *     Route::middleware('jwt.profil')
 *
 * Avec arguments, il restreint l'accès aux profils listés :
 *
 *     Route::middleware('jwt.profil:debiteur')
 *     Route::middleware('jwt.profil:partenaire,agent')
 */
class AuthentifieParProfil
{
    public function handle($request, Closure $next, ...$profilsAutorises)
    {
        try {
            $profil = JWTAuth::parseToken()->getPayload()->get('profil');
        } catch (TokenExpiredException $e) {
            return response()->json([
                'message' => 'Session expirée, veuillez vous reconnecter.',
                'code'    => 'token_expire',
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token absent ou invalide.',
                'code'    => 'token_invalide',
            ], 401);
        }

        // Un token dont le claim `profil` est absent ou fantaisiste ne doit
        // jamais permettre de sélectionner un guard.
        if (! in_array($profil, AuthController::PROFILS, true)) {
            return response()->json([
                'message' => 'Token invalide : profil inconnu.',
                'code'    => 'profil_inconnu',
            ], 401);
        }

        if (! empty($profilsAutorises) && ! in_array($profil, $profilsAutorises, true)) {
            return response()->json([
                'message' => 'Votre profil n\'a pas accès à cette ressource.',
                'code'    => 'profil_non_autorise',
            ], 403);
        }

        // Le nom du guard est identique à celui du profil (config/auth.php).
        auth()->shouldUse($profil);

        // Le compte a pu être supprimé depuis l'émission du token.
        if (! auth()->user()) {
            return response()->json([
                'message' => 'Compte introuvable.',
                'code'    => 'compte_introuvable',
            ], 401);
        }

        return $next($request);
    }
}

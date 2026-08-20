<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Authentification des trois profils métier du front-end Angular.
 *
 * Chaque profil possède son propre guard JWT (voir config/auth.php), le nom du
 * guard étant identique à celui du profil.
 */
class AuthController extends Controller
{
    /**
     * Profils autorisés à se connecter. Sert à la fois à la validation de la
     * requête de connexion et au contrôle du claim `profil` dans le middleware.
     */
    const PROFILS = ['debiteur', 'partenaire', 'agent'];

    /**
     * Applique le middleware à tout sauf la connexion, qui est par nature
     * accessible sans token.
     */
    public function __construct()
    {
        $this->middleware('jwt.profil')->except('login');
    }

    /**
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $donnees = $request->validate([
            'profil'   => 'required|in:' . implode(',', self::PROFILS),
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'profil.required'   => 'Le profil est obligatoire.',
            'profil.in'         => 'Profil inconnu. Valeurs acceptées : ' . implode(', ', self::PROFILS) . '.',
            'email.required'    => 'L\'adresse email est obligatoire.',
            'email.email'       => 'L\'adresse email doit être valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
        ]);

        $profil = $donnees['profil'];

        $token = auth($profil)->attempt([
            'email'    => $donnees['email'],
            'password' => $donnees['password'],
        ]);

        if (! $token) {
            // Message volontairement identique que l'email existe ou non,
            // pour ne pas révéler quels comptes sont enregistrés.
            return response()->json([
                'message' => 'Adresse email ou mot de passe incorrect.',
            ], 401);
        }

        return $this->reponseAvecToken($token, $profil);
    }

    /**
     * GET /api/auth/me
     */
    public function me()
    {
        return response()->json([
            'profil'      => $this->profilCourant(),
            'utilisateur' => auth()->user(),
        ]);
    }

    /**
     * POST /api/auth/refresh
     */
    public function refresh()
    {
        try {
            $token = auth()->refresh();
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Le token ne peut plus être renouvelé, veuillez vous reconnecter.',
            ], 401);
        }

        return $this->reponseAvecToken($token, $this->profilCourant());
    }

    /**
     * POST /api/auth/logout
     *
     * Le token est mis sur liste noire côté serveur : il reste invalide
     * jusqu'à son expiration naturelle.
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * Enveloppe de réponse commune à la connexion et au renouvellement.
     */
    protected function reponseAvecToken($token, $profil)
    {
        return response()->json([
            'token'       => $token,
            'type'        => 'bearer',
            'expires_in'  => auth($profil)->factory()->getTTL() * 60,
            'profil'      => $profil,
            'utilisateur' => auth($profil)->user(),
        ]);
    }

    /**
     * Profil du guard actuellement actif, positionné par le middleware.
     */
    protected function profilCourant()
    {
        return auth()->getDefaultDriver();
    }
}

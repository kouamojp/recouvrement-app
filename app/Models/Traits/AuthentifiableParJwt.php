<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Hash;

/**
 * Comportement d'authentification commun aux trois profils métier :
 * débiteur, partenaire et agent.
 *
 * Le modèle qui utilise ce trait doit déclarer une constante PROFIL. Sa valeur
 * est placée dans le token sous le claim `profil`, où elle sert à la fois au
 * middleware `jwt.profil` côté API et au routage côté Angular.
 */
trait AuthentifiableParJwt
{
    /**
     * Hache le mot de passe avant enregistrement.
     *
     * Une valeur vide est ignorée : à la modification, laisser le champ vide
     * conserve le mot de passe existant. Une valeur déjà hachée en bcrypt est
     * réenregistrée telle quelle, ce qui évite un double hachage.
     */
    public function setPasswordAttribute($value)
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['password'] = preg_match('/^\$2[aby]\$/', $value)
            ? $value
            : Hash::make($value);
    }

    /**
     * Identifiant placé dans le claim `sub` du token.
     *
     * Le cast en chaîne est nécessaire : MongoDB expose des ObjectId, alors que
     * tymon/jwt-auth exige un `sub` scalaire.
     */
    public function getJWTIdentifier()
    {
        return (string) $this->getKey();
    }

    /**
     * Claims additionnels transportés par le token.
     */
    public function getJWTCustomClaims()
    {
        return ['profil' => static::PROFIL];
    }
}

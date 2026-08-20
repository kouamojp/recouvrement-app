<?php

namespace App\Models;

use App\Models\Traits\AuthentifiableParJwt;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Jenssegers\Mongodb\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Partenaire extends Authenticatable implements JWTSubject
{
    use CrudTrait;
    use AuthentifiableParJwt;

    /** Valeur du claim `profil` dans le token JWT. */
    const PROFIL = 'partenaire';

    protected $connection = 'mongodb';

    // Spécifier l'attribut identifiable pour éviter l'utilisation de Doctrine
    public function identifiableAttribute()
    {
        return 'nom';
    }

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'partenaires';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    protected $hidden = ['password'];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    // Relation commentée - MongoDB ne supporte pas belongsToMany avec table pivot SQL
    // Les débiteurs stockent les IDs de partenaires dans leur document
    // public function debiteurs()
    // {
    //     return $this->belongsToMany('App\Models\Debiteur', 'debpart');
    // }
    public function rapport()
    {
        return $this->hasOne('App\Models\Rapport');
    }

    public function dettes()
    {
        return $this->hasMany(Dette::class);
    }

    

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    |
    | Le hachage du mot de passe est fourni par AuthentifiableParJwt.
    |
    */
}
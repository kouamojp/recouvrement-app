<?php

namespace App\Models;

use App\Support\Montants;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Jenssegers\Mongodb\Eloquent\Model;

class Dette extends Model
{
    use CrudTrait;

    protected $connection = 'mongodb';

    // Spécifier l'attribut identifiable pour éviter l'utilisation de Doctrine
    public function identifiableAttribute()
    {
        return 'intitule';
    }

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'dettes';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    protected static function booted()
    {
        static::saving(function ($dette) {
            // Les montants sont saisis en chaînes, parfois avec des séparateurs
            // de milliers : un cast (int) lirait « 1 500 000 » comme 1. Le solde
            // enregistré ici fait foi partout — admin, API, plafond de paiement.
            $dette->solde = Montants::versEntier($dette->montant_reconnu)
                - Montants::versEntier($dette->montant_verse);
        });
    }


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
    public function debiteur()
    {
        return $this->belongsTo('App\Models\Debiteur');
    }
    
    public function partenaire()
    {
        return $this->belongsTo('App\Models\Partenaire');
    }

     public function recu()
    {
        return $this->hasMany('App\Models\Recu');
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
    */
}
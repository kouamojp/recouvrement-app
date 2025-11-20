<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Jenssegers\Mongodb\Eloquent\Model;

class Debiteur extends Model
{
    use CrudTrait;

    protected $connection = 'mongodb';

    // Spécifier l'attribut identifiable pour éviter l'utilisation de Doctrine
    public function identifiableAttribute()
    {
        return 'societe_debitrice';
    }

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'debiteurs';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = [
        'societe_debitrice',
        'gerant',
        'localisation',
        'ville',
        'email',
        'password',
        'telephone',
        'partenaires',  // Array d'IDs de partenaires
        'agent_id',     // ID de l'agent
    ];
    // protected $hidden = [];
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
    // Relations commentées pour MongoDB - elles causent des boucles infinies
    // Les partenaires sont stockés comme tableau d'IDs dans le champ 'partenaires'
    // L'agent_id est stocké directement dans le champ 'agent_id'

    // public function partenaires()
    // {
    //     return $this->hasMany('App\Models\Partenaire', '_id', 'partenaires');
    // }

    // public function agent()
    // {
    //     return $this->belongsTo('App\Models\Agent', 'agent_id');
    // }
    

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
    */
}
<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Jenssegers\Mongodb\Eloquent\Model;

class Recu extends Model
{
    use CrudTrait;

    protected $connection = 'mongodb';

    // Spécifier l'attribut identifiable pour éviter l'utilisation de Doctrine
    public function identifiableAttribute()
    {
        return 'bordereau';
    }

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'recus';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    /**
     * Boot method pour générer automatiquement le bordereau
     */
    protected static function booted()
    {
        static::creating(function ($recu) {
            // Générer automatiquement un bordereau si vide
            if (empty($recu->bordereau)) {
                $recu->bordereau = self::generateBordereau();
            }
        });
    }

    /**
     * Génère un bordereau aléatoire de 14 caractères
     */
    public static function generateBordereau()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $bordereau = '';
        for ($i = 0; $i < 14; $i++) {
            $bordereau .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $bordereau;
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

    public function dette()
    {
        return $this->belongsTo('App\Models\Dette');
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

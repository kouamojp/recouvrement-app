<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Jenssegers\Mongodb\Eloquent\Model;

/**
 * Règlement en ligne d'une dette par le débiteur.
 *
 * Le document conserve l'intégralité du dialogue avec le prestataire :
 * référence externe, charge utile brute et journal des notifications. Un
 * paiement n'est jamais modifié après confirmation — un remboursement donnerait
 * lieu à un autre document.
 */
class Paiement extends Model
{
    use CrudTrait;

    protected $connection = 'mongodb';

    /** Statuts possibles, dans l'ordre du cycle de vie. */
    const STATUT_INITIE = 'initie';
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_REUSSI = 'reussi';
    const STATUT_ECHOUE = 'echoue';
    const STATUT_ANNULE = 'annule';

    /** Moyens proposés au débiteur. */
    const MOYENS = ['carte', 'orange_money', 'mtn_momo', 'paypal'];

    /** Moyens exigeant un numéro de téléphone à débiter. */
    const MOYENS_MOBILES = ['orange_money', 'mtn_momo'];

    protected $table = 'paiements';

    protected $guarded = ['id'];

    protected $casts = [
        'montant' => 'integer',
        'notifications' => 'array',
        'payload' => 'array',
    ];

    protected $dates = ['confirme_le'];

    // Spécifier l'attribut identifiable pour éviter l'utilisation de Doctrine
    public function identifiableAttribute()
    {
        return 'reference';
    }

    protected static function booted()
    {
        static::creating(function ($paiement) {
            if (empty($paiement->reference)) {
                $paiement->reference = self::genererReference();
            }

            if (empty($paiement->statut)) {
                $paiement->statut = self::STATUT_INITIE;
            }

            if (empty($paiement->notifications)) {
                $paiement->notifications = [];
            }
        });
    }

    /**
     * Référence lisible, communiquée au débiteur et utilisée en rapprochement.
     *
     * La date rend le tri manuel évident, le suffixe aléatoire évite d'avoir à
     * sérialiser un compteur — inutilement coûteux sur MongoDB. Les caractères
     * ambigus (0/O, 1/I) sont écartés : cette référence se dicte au téléphone.
     */
    public static function genererReference()
    {
        $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $suffixe = '';

        for ($i = 0; $i < 6; $i++) {
            $suffixe .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }

        return sprintf('%s-%s-%s', config('paiements.prefixe_reference', 'ARC'), date('Ymd'), $suffixe);
    }

    /**
     * Libellé lisible d'un moyen de paiement.
     *
     * Le back-office affiche le mode des reçus tel quel, à côté de ceux saisis
     * à la main (« Chèque », « Espèces ») : un `orange_money` brut y jurerait.
     */
    public static function libelleMoyen($moyen)
    {
        $libelles = [
            'carte' => 'Carte bancaire',
            'orange_money' => 'Orange Money',
            'mtn_momo' => 'MTN Mobile Money',
            'paypal' => 'PayPal',
        ];

        return isset($libelles[$moyen]) ? $libelles[$moyen] : $moyen;
    }

    /** Le prestataire n'a pas encore tranché. */
    public function estEnCours()
    {
        return in_array($this->statut, [self::STATUT_INITIE, self::STATUT_EN_ATTENTE], true);
    }

    public function estReussi()
    {
        return $this->statut === self::STATUT_REUSSI;
    }

    /**
     * Un paiement resté en attente au-delà du délai n'a plus de chance
     * d'aboutir : le code de validation a expiré côté opérateur.
     */
    public function estExpire()
    {
        if (! $this->estEnCours() || ! $this->created_at) {
            return false;
        }

        return $this->created_at->addMinutes((int) config('paiements.delai_expiration', 30))->isPast();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function dette()
    {
        return $this->belongsTo('App\Models\Dette');
    }

    public function debiteur()
    {
        return $this->belongsTo('App\Models\Debiteur');
    }

    public function partenaire()
    {
        return $this->belongsTo('App\Models\Partenaire');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeDuDebiteur($query, $debiteurId)
    {
        return $query->where('debiteur_id', (string) $debiteurId);
    }
}

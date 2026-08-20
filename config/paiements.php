<?php

use App\Support\Montants;

return [

    /*
    |--------------------------------------------------------------------------
    | Devise
    |--------------------------------------------------------------------------
    |
    | Reprise de App\Support\Montants pour qu'il n'y ait qu'une seule source de
    | vérité. Les passerelles qui parlent une autre devise — PayPal — convertissent
    | à l'initiation : voir la clé `taux` correspondante.
    |
    */

    'devise' => env('PAIEMENT_DEVISE', Montants::DEVISE),

    'prefixe_reference' => env('PAIEMENT_PREFIXE_REFERENCE', 'ARC'),

    /*
    |--------------------------------------------------------------------------
    | Délai de validation
    |--------------------------------------------------------------------------
    |
    | Passé ce délai sans confirmation du prestataire, un paiement resté en
    | attente est considéré comme expiré. Le tableau de bord cesse de son côté
    | d'interroger le statut au bout de trois minutes ; ce délai-ci couvre les
    | validations mobile money plus lentes, confirmées par webhook.
    |
    */

    'delai_expiration' => env('PAIEMENT_DELAI_EXPIRATION', 30), // minutes

    /*
    |--------------------------------------------------------------------------
    | Passerelle retenue par moyen de paiement
    |--------------------------------------------------------------------------
    |
    | `factice` n'appelle aucun prestataire : elle confirme le paiement au bout
    | de quelques secondes. C'est le repli automatique lorsqu'une passerelle
    | réelle n'a pas ses identifiants, pour que l'application reste utilisable
    | en développement sans compte marchand.
    |
    */

    'passerelles' => [
        'carte' => env('PAIEMENT_PASSERELLE_CARTE', 'cinetpay'),
        'orange_money' => env('PAIEMENT_PASSERELLE_ORANGE_MONEY', 'cinetpay'),
        'mtn_momo' => env('PAIEMENT_PASSERELLE_MTN_MOMO', 'cinetpay'),
        'paypal' => env('PAIEMENT_PASSERELLE_PAYPAL', 'paypal'),
    ],

    /*
    |--------------------------------------------------------------------------
    | CinetPay — carte bancaire et mobile money d'Afrique centrale
    |--------------------------------------------------------------------------
    |
    | Un seul compte marchand couvre la carte, Orange Money et MTN MoMo au
    | Cameroun. Les montants doivent être des multiples de 5 en XAF, contrainte
    | des opérateurs, appliquée à l'initiation.
    |
    */

    'cinetpay' => [
        'url' => env('CINETPAY_URL', 'https://api-checkout.cinetpay.com/v2'),
        'api_key' => env('CINETPAY_API_KEY'),
        'site_id' => env('CINETPAY_SITE_ID'),
        'devise' => env('CINETPAY_DEVISE', 'XAF'),
        'canaux' => [
            'carte' => 'CREDIT_CARD',
            'orange_money' => 'MOBILE_MONEY',
            'mtn_momo' => 'MOBILE_MONEY',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal — Orders v2
    |--------------------------------------------------------------------------
    |
    | PayPal ne règle pas en franc CFA : le montant est converti à l'initiation
    | au taux ci-dessous, et c'est le montant en FCFA qui reste la référence
    | comptable. Le taux XAF→EUR est fixe (parité fixe avec l'euro) ; toute
    | autre devise de règlement suppose un taux à tenir à jour.
    |
    */

    'paypal' => [
        'url' => env('PAYPAL_URL', 'https://api-m.sandbox.paypal.com'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'devise' => env('PAYPAL_DEVISE', 'EUR'),
        'taux' => env('PAYPAL_TAUX_DEPUIS_FCFA', 655.957),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications déclenchées par un paiement confirmé
    |--------------------------------------------------------------------------
    |
    | L'administration est prévenue à cette adresse ; le partenaire créancier et
    | l'agent en charge le sont à l'adresse portée par leur fiche.
    |
    */

    'notifications' => [
        'admin' => env('PAIEMENT_EMAIL_ADMIN'),
        'partenaire' => env('PAIEMENT_NOTIFIER_PARTENAIRE', true),
        'agent' => env('PAIEMENT_NOTIFIER_AGENT', true),
    ],

];

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Rapport;
use Faker\Generator as Faker;

$commentaires = [
    'Relances téléphoniques hebdomadaires en cours.',
    'Plusieurs débiteurs restent injoignables sur le trimestre.',
    'Protocole d\'accord signé, échéancier respecté à ce jour.',
    'Dossier transmis au service contentieux.',
    'Négociation d\'un rééchelonnement sur six mois.',
    'Recouvrement amiable privilégié, pas de mise en demeure.',
    null,
];

/*
| Les compteurs sont emboîtés pour rester lisibles dans l'admin :
| payement ≤ localisé ≤ actif ≤ transmis.
| partenaire_id est câblé par RapportSeeder.
*/
$factory->define(Rapport::class, function (Faker $faker) use ($commentaires) {
    $transmis = $faker->numberBetween(20, 400);
    $actif = $faker->numberBetween((int) round($transmis * 0.3), $transmis);
    $localiser = $faker->numberBetween((int) round($actif * 0.3), $actif);
    $payement = $faker->numberBetween(0, $localiser);

    return [
        'montant_creance' => $faker->numberBetween(200, 20000) * 5000,
        'nbre_dossier_transmi' => $transmis,
        'nbre_dossier_actif' => $actif,
        'nbre_dossier_localiser' => $localiser,
        'nbre_dossier_payement' => $payement,
        'entr_physiq' => $faker->numberBetween(0, $actif),
        'trans_courier' => $faker->numberBetween(0, $transmis),
        'negoc_en_cours' => $faker->numberBetween(0, $actif),
        'protocol_signe' => $faker->numberBetween(0, $payement),
        'echange_tel' => $faker->numberBetween(0, $transmis * 3),
        'echange_email' => $faker->numberBetween(0, $transmis * 2),
        'commentaire' => $faker->randomElement($commentaires),
    ];
});

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Recu;
use Faker\Generator as Faker;

$modes = [
    'Espèces', 'Virement bancaire', 'Chèque', 'Mobile Money',
    'Orange Money', 'MTN MoMo', 'Dépôt guichet',
];

/*
| `bordereau` n'est pas défini : le hook creating() du modèle Recu le génère
| (14 caractères alphanumériques). debiteur_id, dette_id et partenaire_id sont
| câblés par RecuSeeder, qui les reprend de la dette réglée pour rester cohérent.
*/
$factory->define(Recu::class, function (Faker $faker) use ($modes) {
    return [
        'montant' => $faker->numberBetween(10, 400) * 5000,
        'mode' => $faker->randomElement($modes),
        'date' => $faker->dateTimeBetween('-18 months', 'now')->format('Y-m-d'),
    ];
});

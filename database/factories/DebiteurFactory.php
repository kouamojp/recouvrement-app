<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Debiteur;
use Faker\Generator as Faker;

$fr = \Faker\Factory::create('fr_FR');

$empreinte = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

$villes = [
    'DOUALA', 'YAOUNDÉ', 'BAFOUSSAM', 'GAROUA', 'BAMENDA', 'MAROUA',
    'NGAOUNDÉRÉ', 'BERTOUA', 'KRIBI', 'LIMBÉ', 'BUÉA', 'EBOLOWA',
    'EDÉA', 'KUMBA', 'DSCHANG',
];

/*
| agent_id et partenaires sont volontairement absents : ce sont des
| références vers d'autres collections, câblées par DebiteurSeeder à partir
| des documents réellement créés.
*/
$factory->define(Debiteur::class, function (Faker $faker) use ($fr, $empreinte, $villes) {
    return [
        'societe_debitrice' => mb_strtoupper($fr->company),
        'gerant' => $fr->name,
        'ville' => $faker->randomElement($villes),
        'localisation' => $fr->streetAddress,
        'telephone' => '+237 6' . $faker->numerify('########'),
        'email' => $fr->unique()->safeEmail,
        'password' => $empreinte,
    ];
});

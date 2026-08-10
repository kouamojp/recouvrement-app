<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Partenaire;
use Faker\Generator as Faker;

$fr = \Faker\Factory::create('fr_FR');

$empreinte = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

$villes = [
    'DOUALA', 'YAOUNDÉ', 'BAFOUSSAM', 'GAROUA', 'BAMENDA', 'MAROUA',
    'NGAOUNDÉRÉ', 'BERTOUA', 'KRIBI', 'LIMBÉ', 'BUÉA', 'EBOLOWA',
    'EDÉA', 'KUMBA', 'DSCHANG',
];

$secteurs = [
    'Finance', 'Banque', 'Assurance', 'Télécommunications', 'BTP',
    'Agroalimentaire', 'Transport & Logistique', 'Distribution', 'Énergie',
    'Santé', 'Éducation', 'Immobilier', 'Industrie', 'Hôtellerie',
    'Import-Export',
];

$factory->define(Partenaire::class, function (Faker $faker) use ($fr, $empreinte, $villes, $secteurs) {
    return [
        'nom' => $fr->company,
        'adresse' => $fr->streetAddress,
        'ville' => $faker->randomElement($villes),
        'telephone' => '+237 6' . $faker->numerify('########'),
        'email' => $fr->unique()->companyEmail,
        'password' => $empreinte,
        'secteur' => $faker->randomElement($secteurs),
    ];
});

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Agent;
use Faker\Generator as Faker;

/*
| Générateur francophone : les données réelles de l'application sont
| camerounaises, un Faker en_US produirait des identités incohérentes.
*/
$fr = \Faker\Factory::create('fr_FR');

/*
| Empreinte bcrypt de « password ». Le mutateur de AuthentifiableParJwt
| détecte le préfixe $2y$ et réenregistre la valeur telle quelle : on évite
| ainsi 50 hachages bcrypt par exécution, sans contourner le modèle.
*/
$empreinte = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

$factory->define(Agent::class, function (Faker $faker) use ($fr, $empreinte) {
    return [
        'nom' => $fr->lastName,
        'prenom' => $fr->firstName,
        'email' => $fr->unique()->safeEmail,
        'telephone' => '+237 6' . $faker->numerify('########'),
        'password' => $empreinte,
    ];
});

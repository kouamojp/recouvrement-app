<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Debiteur;
use Faker\Generator as Faker;

$factory->define(Debiteur::class, function (Faker $faker) {
    return [
        'societe_debitrice' => $faker->company,
        'gerant' => $faker->name,
        'localisation' => $faker->address,
        'ville' => $faker->city,
        'email' => $faker->unique()->safeEmail,
        'password' => bcrypt('password'),
        'telephone' => $faker->phoneNumber,
    ];
});
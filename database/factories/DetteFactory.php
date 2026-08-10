<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Dette;
use Faker\Generator as Faker;

$fr = \Faker\Factory::create('fr_FR');

$natures = [
    'Facture impayée', 'Solde de compte', 'Loyer arriéré', 'Prestation non réglée',
    'Livraison non soldée', 'Découvert bancaire', 'Échéance de crédit',
    'Prime d\'assurance', 'Marché de travaux', 'Fourniture de matériel',
];

/*
| Les montants sont enregistrés en chaînes, conformément à App\Support\Montants.
| `solde` n'est pas défini ici : le hook saving() du modèle Dette le calcule.
| debiteur_id et partenaire_id sont câblés par DetteSeeder, qui garantit que le
| partenaire choisi est bien l'un de ceux rattachés au débiteur.
*/
$factory->define(Dette::class, function (Faker $faker) use ($fr, $natures) {
    // Réclamé ≥ reconnu ≥ versé, pour que le solde reste positif et crédible.
    $reclame = $faker->numberBetween(50, 5000) * 5000;
    $reconnu = (int) round($reclame * $faker->numberBetween(40, 100) / 100);
    $verse = (int) round($reconnu * $faker->numberBetween(0, 90) / 100);

    return [
        'intitule' => $faker->randomElement($natures) . ' - ' . $fr->companySuffix,
        'montant_reclame' => (string) $reclame,
        'montant_reconnu' => (string) $reconnu,
        'montant_verse' => (string) $verse,
        'dernier_versement' => $verse > 0
            ? $faker->dateTimeBetween('-18 months', 'now')->format('Y-m-d')
            : null,
        'date_echeance_mensuelle' => $faker->dateTimeBetween('now', '+12 months')->format('Y-m-d'),
    ];
});

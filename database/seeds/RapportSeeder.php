<?php

use App\Models\Partenaire;
use App\Models\Rapport;
use Illuminate\Database\Seeder;

class RapportSeeder extends Seeder
{
    /** Nombre de rapports générés. */
    const NOMBRE = 50;

    /**
     * Un rapport d'activité par partenaire. Le modèle déclare hasOne côté
     * Partenaire : on répartit donc les rapports sur des partenaires distincts
     * tant qu'il y en a assez, plutôt que de tirer au hasard avec doublons.
     *
     * @return void
     */
    public function run()
    {
        $partenaires = Partenaire::pluck('_id')->map(function ($id) {
            return (string) $id;
        })->all();

        if (empty($partenaires)) {
            $this->command->warn('RapportSeeder : aucun partenaire en base, rien à générer.');

            return;
        }

        shuffle($partenaires);

        for ($i = 0; $i < self::NOMBRE; $i++) {
            // Si les partenaires sont moins nombreux que les rapports demandés,
            // on repart au début de la liste.
            $partenaireId = $partenaires[$i % count($partenaires)];

            factory(Rapport::class)->create([
                'partenaire_id' => $partenaireId,
            ]);
        }

        $this->command->info(self::NOMBRE . ' rapports créés.');
    }
}

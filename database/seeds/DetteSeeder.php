<?php

use App\Models\Debiteur;
use App\Models\Dette;
use App\Models\Partenaire;
use Illuminate\Database\Seeder;

class DetteSeeder extends Seeder
{
    /** Nombre de dettes générées. */
    const NOMBRE = 50;

    /**
     * Chaque dette est rattachée à un débiteur, et à un partenaire choisi
     * parmi ceux de ce débiteur. Sans cette contrainte on obtiendrait des
     * dettes réclamées par un partenaire sans lien avec le dossier.
     *
     * Le champ `solde` n'est pas renseigné : le hook saving() de Dette le
     * calcule à partir de montant_reconnu et montant_verse.
     *
     * @return void
     */
    public function run()
    {
        $debiteurs = Debiteur::all(['_id', 'partenaires']);

        if ($debiteurs->isEmpty()) {
            $this->command->warn('DetteSeeder : aucun débiteur en base, rien à générer.');

            return;
        }

        // Repli si un débiteur n'a aucun partenaire rattaché.
        $tousPartenaires = Partenaire::pluck('_id')->map(function ($id) {
            return (string) $id;
        })->all();

        for ($i = 0; $i < self::NOMBRE; $i++) {
            $debiteur = $debiteurs->random();
            $candidats = array_values((array) ($debiteur->partenaires ?: []));

            if (empty($candidats)) {
                $candidats = $tousPartenaires;
            }

            if (empty($candidats)) {
                $this->command->warn('DetteSeeder : aucun partenaire en base, rien à générer.');

                return;
            }

            factory(Dette::class)->create([
                'debiteur_id' => (string) $debiteur->_id,
                'partenaire_id' => (string) $candidats[array_rand($candidats)],
            ]);
        }

        $this->command->info(self::NOMBRE . ' dettes créées.');
    }
}

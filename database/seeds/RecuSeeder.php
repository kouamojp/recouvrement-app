<?php

use App\Models\Dette;
use App\Models\Recu;
use App\Support\Montants;
use Illuminate\Database\Seeder;

class RecuSeeder extends Seeder
{
    /** Nombre de reçus générés. */
    const NOMBRE = 50;

    /**
     * Un reçu constate un versement sur une dette. Il reprend donc le débiteur
     * et le partenaire de cette dette plutôt que d'en tirer au hasard, sinon
     * un reçu pourrait créditer un dossier appartenant à un autre partenaire.
     *
     * Le montant est plafonné au montant déjà versé sur la dette, pour ne pas
     * produire de reçus incohérents avec le solde affiché.
     *
     * Le champ `bordereau` est laissé vide : le hook creating() de Recu le
     * génère automatiquement.
     *
     * @return void
     */
    public function run()
    {
        $dettes = Dette::all(['_id', 'debiteur_id', 'partenaire_id', 'montant_verse']);

        if ($dettes->isEmpty()) {
            $this->command->warn('RecuSeeder : aucune dette en base, rien à générer.');

            return;
        }

        for ($i = 0; $i < self::NOMBRE; $i++) {
            $dette = $dettes->random();
            $verse = Montants::versEntier($dette->montant_verse);

            $attributs = [
                'debiteur_id' => (string) $dette->debiteur_id,
                'dette_id' => (string) $dette->_id,
                'partenaire_id' => (string) $dette->partenaire_id,
            ];

            // Un versement partiel du montant déjà encaissé sur la dette.
            if ($verse > 0) {
                $attributs['montant'] = random_int((int) max(1, round($verse * 0.1)), $verse);
            }

            factory(Recu::class)->create($attributs);
        }

        $this->command->info(self::NOMBRE . ' reçus créés.');
    }
}

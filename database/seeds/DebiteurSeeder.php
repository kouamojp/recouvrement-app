<?php

use App\Models\Agent;
use App\Models\Debiteur;
use App\Models\Partenaire;
use Illuminate\Database\Seeder;

class DebiteurSeeder extends Seeder
{
    /** Nombre de débiteurs générés. */
    const NOMBRE = 50;

    /**
     * Chaque débiteur est rattaché à un agent et à un à trois partenaires.
     *
     * Les identifiants sont enregistrés en chaînes et non en ObjectId : c'est
     * la forme utilisée par les documents existants et attendue par
     * DebiteurResource, qui applique (string) et array_map('strval').
     *
     * @return void
     */
    public function run()
    {
        // La fermeture est nécessaire : Collection::map() passe aussi la clé au
        // callback, ce que strval() refuse.
        $versChaine = function ($id) {
            return (string) $id;
        };

        $agents = Agent::pluck('_id')->map($versChaine)->all();
        $partenaires = Partenaire::pluck('_id')->map($versChaine)->all();

        if (empty($agents) || empty($partenaires)) {
            $this->command->warn(
                'DebiteurSeeder : aucun agent ou partenaire en base. '
                . 'Lancez AgentSeeder et PartenaireSeeder au préalable.'
            );

            return;
        }

        factory(Debiteur::class, self::NOMBRE)->create()->each(
            function (Debiteur $debiteur) use ($agents, $partenaires) {
                $rattaches = (array) array_rand(
                    array_flip($partenaires),
                    min(count($partenaires), random_int(1, 3))
                );

                $debiteur->agent_id = $agents[array_rand($agents)];
                $debiteur->partenaires = array_values($rattaches);
                $debiteur->save();
            }
        );

        $this->command->info(self::NOMBRE . ' débiteurs créés et rattachés.');
    }
}

<?php

use App\Models\Agent;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    /** Nombre d'agents générés. */
    const NOMBRE = 50;

    /**
     * Les agents n'ont aucune dépendance : ils sont créés en premier et servent
     * ensuite de cible au champ agent_id des débiteurs.
     *
     * @return void
     */
    public function run()
    {
        factory(Agent::class, self::NOMBRE)->create();

        $this->command->info(self::NOMBRE . ' agents créés.');
    }
}

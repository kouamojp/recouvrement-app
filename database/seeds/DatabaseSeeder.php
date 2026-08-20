<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * L'ordre est imposé par les références entre collections :
     *
     *   Agent ─────┐
     *              ├──> Debiteur ──> Dette ──> Recu
     *   Partenaire ┘        │           ↑
     *              └────────┴───────────┘
     *   Partenaire ──> Rapport
     *
     * Les seeders sont additifs : ils n'effacent rien. Relancer `db:seed`
     * ajoute donc un nouveau lot par-dessus les données existantes.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(AgentSeeder::class);
        $this->call(PartenaireSeeder::class);
        $this->call(DebiteurSeeder::class);
        $this->call(DetteSeeder::class);
        $this->call(RecuSeeder::class);
        $this->call(RapportSeeder::class);
    }
}

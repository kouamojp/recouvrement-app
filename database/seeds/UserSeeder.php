<?php

use App\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /** Nombre d'utilisateurs d'administration générés. */
    const NOMBRE = 50;

    /**
     * Comptes d'administration Backpack (collection `users`), distincts des
     * trois profils métier agent / partenaire / débiteur.
     *
     * @return void
     */
    public function run()
    {
        factory(User::class, self::NOMBRE)->create();

        $this->command->info(self::NOMBRE . ' utilisateurs d\'administration créés.');
    }
}

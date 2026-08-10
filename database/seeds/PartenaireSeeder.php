<?php

use App\Models\Partenaire;
use Illuminate\Database\Seeder;

class PartenaireSeeder extends Seeder
{
    /** Nombre de partenaires générés. */
    const NOMBRE = 50;

    /**
     * Sans dépendance également. Les partenaires sont référencés ensuite par
     * les débiteurs, les dettes, les reçus et les rapports.
     *
     * @return void
     */
    public function run()
    {
        factory(Partenaire::class, self::NOMBRE)->create();

        $this->command->info(self::NOMBRE . ' partenaires créés.');
    }
}

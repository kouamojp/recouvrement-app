<?php

use Illuminate\Database\Seeder;
use App\Models\Debiteur;

class DebiteurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*Debiteur::create([
            'societe_debitrice' => 'Admin',
            'gerant' => 'Test',
            'localisation' => 'test',
            'ville' => 'Douala',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'telephone' => '690703689',
        ]);*/

        factory(App\Models\Debiteur::class, 10)->create();
    }
}
<?php

namespace Tests\Feature;

use App\Models\Debiteur;
use App\Models\Recu;
use Tests\TestCase;

/**
 * GET /api/debiteur/me/recus — reçus de versement du débiteur connecté.
 */
class RecusDebiteurTest extends TestCase
{
    /** @var Debiteur */
    protected $debiteur;

    /** @var Debiteur */
    protected $autre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->debiteur = $this->creerDebiteur('ZZ Test Societe');
        $this->autre = $this->creerDebiteur('ZZ Autre Societe');
    }

    protected function tearDown(): void
    {
        Recu::whereIn('debiteur_id', [
            (string) $this->debiteur->_id,
            (string) $this->autre->_id,
        ])->delete();

        $this->debiteur->delete();
        $this->autre->delete();

        parent::tearDown();
    }

    /** @test */
    public function les_recus_sont_rendus_du_plus_recent_au_plus_ancien()
    {
        $this->creerRecu($this->debiteur, '2026-01-10', 50000, 'Chèque');
        $this->creerRecu($this->debiteur, '2026-07-27', 120000, 'Orange Money');
        $this->creerRecu($this->debiteur, '2025-08-01', 30000, 'Espèces');

        $reponse = $this->reponsePour($this->debiteur);

        $reponse->assertStatus(200);

        $dates = array_column($reponse->json(), 'date');

        $this->assertSame(['2026-07-27', '2026-01-10', '2025-08-01'], $dates);
    }

    /** @test */
    public function un_debiteur_ne_voit_jamais_les_recus_d_un_autre()
    {
        $this->creerRecu($this->debiteur, '2026-07-27', 120000, 'Orange Money');
        $this->creerRecu($this->autre, '2026-07-28', 999000, 'Chèque');

        $montants = array_column($this->reponsePour($this->debiteur)->json(), 'montant');

        $this->assertSame([120000], $montants);
    }

    /** @test */
    public function le_recu_expose_les_champs_attendus_par_le_tableau_de_bord()
    {
        $recu = $this->creerRecu($this->debiteur, '2026-07-27', 120000, 'Orange Money');

        $corps = $this->reponsePour($this->debiteur)->json();

        $this->assertCount(1, $corps);
        $this->assertSame((string) $recu->_id, $corps[0]['id']);
        $this->assertSame($recu->bordereau, $corps[0]['bordereau']);
        $this->assertSame(120000, $corps[0]['montant']);
        $this->assertSame('FCFA', $corps[0]['devise']);
        $this->assertSame('Orange Money', $corps[0]['mode']);
        $this->assertArrayHasKey('dette_id', $corps[0]);
        $this->assertArrayHasKey('paiement_id', $corps[0]);
    }

    /** @test */
    public function un_montant_saisi_avec_des_espaces_est_normalise_en_entier()
    {
        // Le back-office laisse saisir « 1 500 000 » : l'API doit rendre un entier.
        $this->creerRecu($this->debiteur, '2026-07-27', '1 500 000', 'Chèque');

        $corps = $this->reponsePour($this->debiteur)->json();

        $this->assertSame(1500000, $corps[0]['montant']);
    }

    protected function reponsePour(Debiteur $debiteur)
    {
        return $this->getJson('api/debiteur/me/recus', [
            'Authorization' => 'Bearer ' . auth('debiteur')->login($debiteur),
        ]);
    }

    protected function creerDebiteur($societe)
    {
        return Debiteur::create([
            'societe_debitrice' => $societe, 'gerant' => 'Test',
            'ville' => 'Douala', 'localisation' => 'Akwa', 'telephone' => '000',
            'email' => 'zz-' . uniqid() . '@test.invalid',
            'password' => bcrypt('motdepasse'),
        ]);
    }

    protected function creerRecu(Debiteur $debiteur, $date, $montant, $mode)
    {
        return Recu::create([
            'debiteur_id' => (string) $debiteur->_id,
            'date' => $date,
            'montant' => $montant,
            'mode' => $mode,
        ]);
    }
}

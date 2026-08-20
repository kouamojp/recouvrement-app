<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Debiteur;
use App\User;
use Tests\TestCase;

/**
 * Attribution d'un agent de recouvrement à un débiteur depuis le back-office.
 *
 * Le formulaire d'admin déclarait le select dans un champ `custom_html`, dont
 * le `name` ne correspondait pas à celui de l'input : Backpack n'enregistre que
 * les entrées de la requête portant le nom d'un champ déclaré, et l'attribution
 * partait donc à la poubelle sans le moindre message. L'agent ne voyait ensuite
 * aucun débiteur dans son espace.
 */
class AttributionAgentTest extends TestCase
{
    /** @var User */
    protected $admin;

    /** @var Agent */
    protected $agent;

    /** @var Debiteur */
    protected $debiteur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'ZZ Test Admin',
            'email' => 'zz-admin-' . uniqid() . '@test.invalid',
            'password' => bcrypt('motdepasse'),
        ]);

        $this->agent = Agent::create([
            'nom' => 'ZZTest', 'prenom' => 'Agent',
            'email' => 'zz-agent-' . uniqid() . '@test.invalid',
            'telephone' => '000', 'password' => bcrypt('motdepasse'),
        ]);

        $this->debiteur = Debiteur::create([
            'societe_debitrice' => 'ZZ Test Societe', 'gerant' => 'Test',
            'ville' => 'Douala', 'localisation' => 'Akwa', 'telephone' => '000',
            'email' => 'zz-debiteur-' . uniqid() . '@test.invalid',
            'password' => bcrypt('motdepasse'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->debiteur->delete();
        $this->agent->delete();
        $this->admin->delete();

        parent::tearDown();
    }

    /** @test */
    public function le_formulaire_admin_enregistre_l_agent_choisi()
    {
        $reponse = $this->actingAs($this->admin, 'backpack')
            ->put('admin/debiteur/' . $this->debiteur->_id, [
                // Backpack retrouve l'entrée par la clé du modèle, `_id` sur MongoDB.
                '_id' => (string) $this->debiteur->_id,
                'societe_debitrice' => $this->debiteur->societe_debitrice,
                'gerant' => $this->debiteur->gerant,
                'ville' => $this->debiteur->ville,
                'localisation' => $this->debiteur->localisation,
                'telephone' => $this->debiteur->telephone,
                'email' => $this->debiteur->email,
                'password' => '',
                'agent_id' => (string) $this->agent->_id,
            ]);

        $reponse->assertStatus(302);

        $this->debiteur->refresh();

        $this->assertSame((string) $this->agent->_id, (string) $this->debiteur->agent_id);
    }

    /** @test */
    public function l_agent_voit_ensuite_le_debiteur_dans_son_espace()
    {
        $this->debiteur->agent_id = (string) $this->agent->_id;
        $this->debiteur->save();

        $reponse = $this->getJson('api/agent/me/debiteurs', [
            'Authorization' => 'Bearer ' . auth('agent')->login($this->agent),
        ]);

        $reponse->assertStatus(200);

        $societes = array_column($reponse->json(), 'societe_debitrice');

        $this->assertContains($this->debiteur->societe_debitrice, $societes);
    }
}

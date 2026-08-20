<?php

namespace Tests\Unit;

use App\Models\Paiement;
use App\Services\Paiement\PasserelleFactice;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Règles du paiement qui ne touchent pas la base : référence, cycle de vie,
 * expiration et passerelle de développement.
 */
class PaiementTest extends TestCase
{
    /** @test */
    public function la_reference_porte_le_prefixe_et_la_date_du_jour()
    {
        config(['paiements.prefixe_reference' => 'ARC']);

        $reference = Paiement::genererReference();

        $this->assertMatchesRegularExpression('/^ARC-\d{8}-[A-Z2-9]{6}$/', $reference);
        $this->assertSame(date('Ymd'), substr($reference, 4, 8));
    }

    /** @test */
    public function la_reference_ecarte_les_caracteres_ambigus()
    {
        // Elle se dicte au téléphone : ni 0/O ni 1/I.
        for ($i = 0; $i < 20; $i++) {
            $suffixe = substr(Paiement::genererReference(), -6);

            $this->assertSame(0, preg_match('/[01OI]/', $suffixe), $suffixe);
        }
    }

    /** @test */
    public function deux_references_consecutives_different()
    {
        $this->assertNotSame(Paiement::genererReference(), Paiement::genererReference());
    }

    /** @test */
    public function seuls_les_statuts_initie_et_en_attente_sont_en_cours()
    {
        $enCours = [Paiement::STATUT_INITIE, Paiement::STATUT_EN_ATTENTE];
        $tranches = [Paiement::STATUT_REUSSI, Paiement::STATUT_ECHOUE, Paiement::STATUT_ANNULE];

        foreach ($enCours as $statut) {
            $this->assertTrue((new Paiement(['statut' => $statut]))->estEnCours(), $statut);
        }

        foreach ($tranches as $statut) {
            $this->assertFalse((new Paiement(['statut' => $statut]))->estEnCours(), $statut);
        }
    }

    /** @test */
    public function un_paiement_en_attente_expire_au_dela_du_delai_configure()
    {
        config(['paiements.delai_expiration' => 30]);

        $paiement = new Paiement(['statut' => Paiement::STATUT_EN_ATTENTE]);
        $paiement->created_at = Carbon::now()->subMinutes(31);

        $this->assertTrue($paiement->estExpire());

        $paiement->created_at = Carbon::now()->subMinutes(29);

        $this->assertFalse($paiement->estExpire());
    }

    /** @test */
    public function un_paiement_deja_reussi_n_expire_jamais()
    {
        $paiement = new Paiement(['statut' => Paiement::STATUT_REUSSI]);
        $paiement->created_at = Carbon::now()->subYear();

        $this->assertFalse($paiement->estExpire());
    }

    /** @test */
    public function la_passerelle_factice_confirme_une_fois_le_delai_ecoule()
    {
        $passerelle = new PasserelleFactice();
        $paiement = new Paiement(['statut' => Paiement::STATUT_EN_ATTENTE]);

        $paiement->created_at = Carbon::now();
        $this->assertSame(Paiement::STATUT_EN_ATTENTE, $passerelle->verifier($paiement));

        $paiement->created_at = Carbon::now()->subSeconds(PasserelleFactice::DELAI_CONFIRMATION + 1);
        $this->assertSame(Paiement::STATUT_REUSSI, $passerelle->verifier($paiement));
    }

    /** @test */
    public function la_passerelle_factice_n_ouvre_aucune_redirection()
    {
        $paiement = new Paiement(['reference' => 'ARC-TEST', 'moyen' => 'orange_money']);

        (new PasserelleFactice())->initier($paiement, 'http://localhost:4200/debiteur/paiements');

        $this->assertNull($paiement->url_redirection);
        $this->assertSame(Paiement::STATUT_EN_ATTENTE, $paiement->statut);
        $this->assertNotEmpty($paiement->instruction);
    }
}

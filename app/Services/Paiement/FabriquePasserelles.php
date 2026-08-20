<?php

namespace App\Services\Paiement;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Résout la passerelle à employer pour un moyen de paiement donné.
 *
 * Une passerelle dont les identifiants marchands manquent est remplacée par la
 * passerelle factice, en développement seulement : mieux vaut un tunnel simulé
 * qu'une pile d'erreurs pour qui travaille sans compte prestataire. En
 * production, l'absence d'identifiants est une erreur de déploiement et doit
 * s'entendre comme telle.
 */
class FabriquePasserelles
{
    /** @var Client */
    protected $http;

    /** @var array Instances déjà construites, indexées par nom. */
    protected $instances = [];

    public function __construct(Client $http)
    {
        $this->http = $http;
    }

    /**
     * @param  string  $moyen  carte, orange_money, mtn_momo ou paypal.
     * @return Passerelle
     */
    public function pourMoyen($moyen)
    {
        $nom = config('paiements.passerelles.' . $moyen);

        if (! $nom) {
            throw new EchecPasserelle("Aucune passerelle configurée pour « {$moyen} ».");
        }

        return $this->parNom($nom);
    }

    /**
     * @param  string  $nom
     * @return Passerelle
     */
    public function parNom($nom)
    {
        if (isset($this->instances[$nom])) {
            return $this->instances[$nom];
        }

        return $this->instances[$nom] = $this->construire($nom);
    }

    protected function construire($nom)
    {
        switch ($nom) {
            case 'cinetpay':
                $config = config('paiements.cinetpay');

                if (empty($config['api_key']) || empty($config['site_id'])) {
                    return $this->replier('cinetpay', 'CINETPAY_API_KEY / CINETPAY_SITE_ID');
                }

                return new PasserelleCinetPay($this->http, $config);

            case 'paypal':
                $config = config('paiements.paypal');

                if (empty($config['client_id']) || empty($config['secret'])) {
                    return $this->replier('paypal', 'PAYPAL_CLIENT_ID / PAYPAL_SECRET');
                }

                return new PasserellePayPal($this->http, $config);

            case 'factice':
                return $this->factice();
        }

        throw new EchecPasserelle("Passerelle inconnue : « {$nom} ».");
    }

    protected function replier($nom, $variables)
    {
        $message = "Passerelle {$nom} non configurée ({$variables}).";

        if (app()->environment('production')) {
            throw new EchecPasserelle($message);
        }

        Log::warning($message . ' Repli sur la passerelle factice.');

        return $this->factice();
    }

    protected function factice()
    {
        if (app()->environment('production')) {
            throw new EchecPasserelle('La passerelle factice est interdite en production.');
        }

        return new PasserelleFactice();
    }
}

<?php

namespace App\Services\Paiement;

use App\Models\Paiement;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * PayPal — API Orders v2.
 *
 * PayPal ne règle pas en franc CFA : le montant est converti à l'initiation
 * dans la devise du compte marchand. La dette reste comptabilisée en FCFA, seul
 * montant qui fasse foi ; le montant converti n'existe que pour le prestataire.
 */
class PasserellePayPal implements Passerelle
{
    /** @var Client */
    protected $http;

    /** @var array */
    protected $config;

    public function __construct(Client $http, array $config)
    {
        $this->http = $http;
        $this->config = $config;
    }

    public function nom()
    {
        return 'paypal';
    }

    public function initier(Paiement $paiement, $urlRetour)
    {
        $commande = $this->appeler('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $paiement->reference,
                'description' => 'Reglement dette ' . $paiement->reference,
                'amount' => [
                    'currency_code' => $this->config['devise'],
                    'value' => $this->montantConverti($paiement),
                ],
            ]],
            'application_context' => [
                'brand_name' => config('app.name'),
                'user_action' => 'PAY_NOW',
                'return_url' => $urlRetour,
                'cancel_url' => $urlRetour,
            ],
        ]);

        $lien = $this->lienApprobation($commande);

        if (! $lien) {
            throw new EchecPasserelle("PayPal n'a pas renvoyé de lien d'approbation.");
        }

        $paiement->reference_externe = $commande['id'];
        $paiement->url_redirection = $lien;
        $paiement->statut = Paiement::STATUT_EN_ATTENTE;
        $paiement->payload = $commande;
    }

    /**
     * Relit la commande et l'encaisse dès qu'elle est approuvée.
     *
     * Une commande `APPROVED` n'est pas de l'argent reçu : tant que la capture
     * n'a pas eu lieu, rien n'est débité. C'est donc ici, et pas au retour du
     * navigateur, que l'encaissement est déclenché.
     */
    public function verifier(Paiement $paiement)
    {
        $identifiant = $paiement->reference_externe;

        if (! $identifiant) {
            return Paiement::STATUT_EN_ATTENTE;
        }

        $commande = $this->appeler('GET', '/v2/checkout/orders/' . $identifiant);
        $statut = isset($commande['status']) ? $commande['status'] : '';

        if ($statut === 'APPROVED') {
            $commande = $this->appeler('POST', '/v2/checkout/orders/' . $identifiant . '/capture');
            $statut = isset($commande['status']) ? $commande['status'] : '';
            $paiement->payload = $commande;
        }

        if ($statut === 'COMPLETED') {
            return Paiement::STATUT_REUSSI;
        }

        if ($statut === 'VOIDED') {
            $paiement->message_echec = 'Commande PayPal annulée.';

            return Paiement::STATUT_ANNULE;
        }

        return Paiement::STATUT_EN_ATTENTE;
    }

    public function referenceDepuisWebhook(array $charge)
    {
        if (isset($charge['resource']['id'])) {
            return $charge['resource']['id'];
        }

        return isset($charge['resource']['supplementary_data']['related_ids']['order_id'])
            ? $charge['resource']['supplementary_data']['related_ids']['order_id']
            : null;
    }

    /** Montant en devise PayPal, à deux décimales, arrondi au centime inférieur. */
    protected function montantConverti(Paiement $paiement)
    {
        $taux = (float) $this->config['taux'];

        if ($taux <= 0) {
            throw new EchecPasserelle('Taux de conversion PayPal non configuré.');
        }

        return number_format(floor(((int) $paiement->montant / $taux) * 100) / 100, 2, '.', '');
    }

    protected function lienApprobation(array $commande)
    {
        foreach (isset($commande['links']) ? $commande['links'] : [] as $lien) {
            if (isset($lien['rel']) && $lien['rel'] === 'approve') {
                return $lien['href'];
            }
        }

        return null;
    }

    protected function appeler($methode, $chemin, array $corps = null)
    {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->jeton(),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 20,
        ];

        if ($corps !== null) {
            $options['json'] = $corps;
        }

        try {
            $reponse = $this->http->request($methode, rtrim($this->config['url'], '/') . $chemin, $options);
        } catch (GuzzleException $e) {
            throw new EchecPasserelle('PayPal est injoignable : ' . $e->getMessage(), false, $e);
        }

        $decodee = json_decode((string) $reponse->getBody(), true);

        if (! is_array($decodee)) {
            throw new EchecPasserelle('Réponse illisible de PayPal.');
        }

        return $decodee;
    }

    /**
     * Jeton OAuth, mis en cache un peu moins longtemps que sa durée de vie.
     */
    protected function jeton()
    {
        return cache()->remember('paypal.jeton', 25 * 60, function () {
            try {
                $reponse = $this->http->post(rtrim($this->config['url'], '/') . '/v1/oauth2/token', [
                    'auth' => [$this->config['client_id'], $this->config['secret']],
                    'form_params' => ['grant_type' => 'client_credentials'],
                    'timeout' => 20,
                ]);
            } catch (GuzzleException $e) {
                throw new EchecPasserelle('Authentification PayPal impossible : ' . $e->getMessage(), false, $e);
            }

            $decodee = json_decode((string) $reponse->getBody(), true);

            if (empty($decodee['access_token'])) {
                throw new EchecPasserelle('PayPal a refusé les identifiants marchands.', true);
            }

            return $decodee['access_token'];
        });
    }
}

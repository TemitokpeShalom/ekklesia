<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Point 15 (passerelle de paiement, Afrique de l'Ouest/Centrale) : appels
 * directs a l'API REST FedaPay via Guzzle (deja une dependance du projet),
 * sans SDK supplementaire a installer sur le serveur. Reference : Workbench
 * FedaPay, documentation API (docs.fedapay.com).
 *
 * IMPORTANT - jamais teste avec de vraies cles depuis cet environnement de
 * developpement (pas d'acces au tableau de bord FedaPay, seules des cles
 * "live" ont ete fournies, pas de cles sandbox). La forme exacte des
 * reponses JSON de FedaPay peut donc varier legerement de ce qui est
 * anticipe ici - extractTransaction() reste volontairement tolerant (essaie
 * plusieurs formes connues) et chaque appel journalise sa reponse brute
 * (sans jamais journaliser la cle secrete) pour permettre un diagnostic
 * rapide au premier vrai paiement.
 */
class FedaPayClient
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => rtrim($this->baseUrl(), '/').'/',
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer '.config('fedapay.secret_key'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function baseUrl(): string
    {
        $environment = config('fedapay.environment', 'sandbox');

        return config("fedapay.base_url.$environment") ?? config('fedapay.base_url.sandbox');
    }

    /**
     * Cree une transaction FedaPay. $payload suit le format documente :
     * description, amount (entier), currency (['iso' => ...]), callback_url,
     * custom_metadata, customer (facultatif).
     *
     * @return array{id: int|string|null, status: string|null, raw: array}
     */
    public function createTransaction(array $payload): array
    {
        $response = $this->post('transactions', $payload);
        $transaction = $this->extractTransaction($response);

        if (empty($transaction['id'])) {
            Log::error('FedaPay: creation de transaction sans id exploitable.', ['response' => $response]);
            throw new RuntimeException("La creation de la transaction FedaPay n'a pas renvoye d'identifiant exploitable.");
        }

        return $transaction + ['raw' => $response];
    }

    /**
     * Genere le jeton de paiement (URL de la page de paiement hebergee)
     * pour une transaction deja creee.
     *
     * @return array{url: string|null, raw: array}
     */
    public function generateToken(int|string $transactionId): array
    {
        $response = $this->post("transactions/$transactionId/token", []);

        $url = $response['url']
            ?? $response['token']['url']
            ?? $response['v1/token']['url']
            ?? null;

        if (! $url) {
            Log::error('FedaPay: generation de jeton sans URL exploitable.', ['response' => $response]);
            throw new RuntimeException("La generation du jeton de paiement FedaPay n'a pas renvoye d'URL exploitable.");
        }

        return ['url' => $url, 'raw' => $response];
    }

    /**
     * Relit une transaction existante (utilise par le webhook, qui ne recoit
     * qu'un evenement "fin" avec l'id de la transaction, jamais son detail
     * complet).
     *
     * @return array{id: int|string|null, status: string|null, amount: mixed, currency: mixed, custom_metadata: array, raw: array}
     */
    public function retrieveTransaction(int|string $transactionId): array
    {
        $response = $this->get("transactions/$transactionId");

        return $this->extractTransaction($response) + ['raw' => $response];
    }

    private function post(string $path, array $payload): array
    {
        try {
            $response = $this->http->post($path, ['json' => $payload]);
        } catch (GuzzleException $e) {
            Log::error("FedaPay: echec de l'appel POST $path.", ['message' => $e->getMessage()]);
            throw new RuntimeException("Echec de l'appel a FedaPay ($path) : ".$e->getMessage(), previous: $e);
        }

        $decoded = json_decode((string) $response->getBody(), true);
        Log::info("FedaPay: reponse POST $path.", ['response' => $decoded]);

        return is_array($decoded) ? $decoded : [];
    }

    private function get(string $path): array
    {
        try {
            $response = $this->http->get($path);
        } catch (GuzzleException $e) {
            Log::error("FedaPay: echec de l'appel GET $path.", ['message' => $e->getMessage()]);
            throw new RuntimeException("Echec de l'appel a FedaPay ($path) : ".$e->getMessage(), previous: $e);
        }

        $decoded = json_decode((string) $response->getBody(), true);
        Log::info("FedaPay: reponse GET $path.", ['response' => $decoded]);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * La documentation FedaPay n'ayant pas pu etre confirmee avec un appel
     * reel depuis cet environnement, cette methode essaie plusieurs formes
     * de reponse connues (objet a plat, ou imbrique sous "v1/transaction"
     * / "transaction") plutot que de supposer une seule forme figee.
     */
    private function extractTransaction(array $response): array
    {
        $tx = $response['v1/transaction'] ?? $response['transaction'] ?? $response;

        return [
            'id' => $tx['id'] ?? null,
            'status' => $tx['status'] ?? null,
            'amount' => $tx['amount'] ?? null,
            'currency' => $tx['currency'] ?? null,
            'custom_metadata' => $tx['custom_metadata'] ?? [],
        ];
    }
}

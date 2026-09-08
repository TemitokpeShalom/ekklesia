<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Point 15 (passerelle de paiement, diaspora) : verifie une transaction
 * BNB Smart Chain a partir de son seul hash, en lisant la chaine
 * publiquement via JSON-RPC (aucune cle privee, aucune signature, aucun
 * transfert de fonds effectue par l'application - uniquement une lecture
 * de ce qui a deja ete envoye par le payeur lui-meme). Reconnait deux cas :
 * un envoi direct en BNB natif, ou un transfert du jeton USDT (BEP-20).
 * Le destinataire est TOUJOURS verifie contre l'adresse du ministere
 * (config('crypto.wallet_address')) : une transaction confirmee mais
 * envoyee ailleurs n'est jamais retenue comme un paiement valide.
 */
class CryptoPaymentVerifier
{
    /**
     * @return array{confirmed: bool, to: ?string, amount: ?string, token: ?string, reason: ?string}
     */
    public function verify(string $txHash): array
    {
        $walletAddress = strtolower((string) config('crypto.wallet_address'));

        $receipt = $this->rpcCall('eth_getTransactionReceipt', [$txHash]);

        if (! $receipt) {
            return $this->rejected('introuvable_ou_en_attente');
        }

        if (($receipt['status'] ?? null) !== '0x1') {
            return $this->rejected('echouee_on_chain');
        }

        $usdtContract = strtolower(config('crypto.usdt_bep20_contract'));
        $receiptTo = strtolower($receipt['to'] ?? '');

        // Transfert du jeton USDT (BEP-20) : le "to" de la transaction est
        // le contrat du jeton, le vrai destinataire est encode dans le
        // journal Transfer(address indexed from, address indexed to, uint256 value).
        if ($receiptTo === $usdtContract) {
            foreach ($receipt['logs'] ?? [] as $log) {
                if (strtolower($log['address'] ?? '') !== $usdtContract) {
                    continue;
                }
                $topics = $log['topics'] ?? [];
                if (count($topics) < 3) {
                    continue;
                }
                $to = strtolower('0x'.substr($topics[2], -40));
                if ($to !== $walletAddress) {
                    return $this->rejected('destinataire_incorrect');
                }

                $rawValue = $this->hexToNumber($log['data'] ?? '0x0');
                $decimals = $this->usdtDecimals();

                return [
                    'confirmed' => true,
                    'to' => $to,
                    'amount' => $this->formatAmount($rawValue, $decimals),
                    'token' => 'USDT',
                    'reason' => null,
                ];
            }

            return $this->rejected('transfert_usdt_illisible');
        }

        // Sinon, envoi direct en BNB natif.
        if ($receiptTo !== $walletAddress) {
            return $this->rejected('destinataire_incorrect');
        }

        $transaction = $this->rpcCall('eth_getTransactionByHash', [$txHash]);
        $rawValue = $this->hexToNumber($transaction['value'] ?? '0x0');

        return [
            'confirmed' => true,
            'to' => $receiptTo,
            'amount' => $this->formatAmount($rawValue, 18),
            'token' => 'BNB',
            'reason' => null,
        ];
    }

    private function rejected(string $reason): array
    {
        return ['confirmed' => false, 'to' => null, 'amount' => null, 'token' => null, 'reason' => $reason];
    }

    private function usdtDecimals(): int
    {
        // decimals() de l'interface ERC20 standard (selecteur fixe
        // 0x313ce567), lu dynamiquement plutot que suppose en dur.
        $result = $this->rpcCall('eth_call', [
            ['to' => config('crypto.usdt_bep20_contract'), 'data' => '0x313ce567'],
            'latest',
        ]);

        return $result ? $this->hexToNumber($result) : 18;
    }

    /**
     * Simple division flottante (pas de bcmath, disponibilite non garantie
     * sur le serveur) : suffisant ici, ce montant n'est qu'un repere affiche
     * a l'administrateur et conserve pour memoire, jamais une valeur
     * recalculee ou comparee au centime pres.
     */
    private function formatAmount(int|float $rawValue, int $decimals): string
    {
        return number_format($rawValue / (10 ** $decimals), 8, '.', '');
    }

    private function hexToNumber(string $hex): int|float
    {
        return hexdec(str_starts_with($hex, '0x') ? substr($hex, 2) : $hex);
    }

    private function rpcCall(string $method, array $params): mixed
    {
        foreach (config('crypto.rpc_endpoints', []) as $endpoint) {
            try {
                $client = new Client(['timeout' => 10]);
                $response = $client->post($endpoint, [
                    'json' => ['jsonrpc' => '2.0', 'method' => $method, 'params' => $params, 'id' => 1],
                ]);
                $decoded = json_decode((string) $response->getBody(), true);

                if (isset($decoded['error'])) {
                    Log::warning("Crypto RPC ($method) erreur sur $endpoint.", ['error' => $decoded['error']]);

                    continue;
                }

                return $decoded['result'] ?? null;
            } catch (\Throwable $e) {
                Log::warning("Crypto RPC ($method) injoignable sur $endpoint.", ['message' => $e->getMessage()]);

                continue;
            }
        }

        Log::error("Crypto RPC ($method) : tous les noeuds publics ont echoue.");

        return null;
    }
}

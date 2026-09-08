<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Point 15 (passerelle de paiement, diaspora) : conversion XOF -> USDT, a la
 * demande de l'administrateur, pour deux usages bien distincts.
 *
 * 1. Affichage sur la page Abonnement (usdtEstimateForXof) : un repere pour
 *    la personne qui va payer, recalcule a chaque chargement de la page
 *    (pas un flux temps reel qui se rafraichirait tout seul dans l'onglet
 *    ouvert - un cours en cache de quelques minutes suffit largement pour
 *    cet usage et evite d'interroger les API externes a chaque affichage).
 *    Si le cours n'est pas disponible, la ligne est simplement absente :
 *    jamais un montant invente.
 *
 * 2. Verification du montant recu (usdEquivalent / bnbUsdRate, utilises par
 *    CryptoPaymentVerifier) : ici, jamais de repli silencieux sur une
 *    valeur perimee ou en dur - un cours indisponible fait echouer la
 *    verification plutot que d'accepter un paiement sur la base d'un
 *    montant devine.
 *
 * Le franc CFA (XOF) est arrime a l'euro a un taux FIXE par traite
 * (655,957 XOF = 1 EUR, jamais autre chose) : seul le taux EUR/USD est
 * reellement variable et doit venir d'une source live. Le taux BNB/USD
 * est lui bien plus volatil (marche crypto) et jamais mis en cache
 * longtemps.
 */
class ExchangeRateService
{
    /** XOF pour 1 EUR - parite fixe du franc CFA, pas un taux de marche. */
    public const XOF_PER_EUR = 655.957;

    /**
     * Estimation USDT pour un montant XOF, pour affichage uniquement.
     * Retourne null si aucun cours n'est disponible (jamais une valeur
     * inventee) - a l'appelant de simplement ne rien afficher dans ce cas.
     */
    public function usdtEstimateForXof(float $xofAmount): ?float
    {
        try {
            return round($this->xofToUsd($xofAmount), 2);
        } catch (RuntimeException $e) {
            Log::warning('ExchangeRate: estimation USDT indisponible.', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Meme conversion, mais pour la verification d'un paiement : jamais de
     * repli silencieux, une indisponibilite du cours doit faire echouer
     * l'appelant plutot que de deviner un montant.
     *
     * @throws RuntimeException si le cours EUR/USD est indisponible
     */
    public function xofToUsd(float $xofAmount): float
    {
        $eurUsd = $this->eurUsdRate();

        return ($xofAmount / self::XOF_PER_EUR) * $eurUsd;
    }

    /**
     * @throws RuntimeException si le cours BNB/USD est indisponible
     */
    public function usdToBnb(float $usdAmount): float
    {
        return $usdAmount / $this->bnbUsdRate();
    }

    /**
     * Taux EUR/USD, mis en cache 1 heure (le marche des changes ne bouge
     * pas assez vite pour que ce soit genant, meme pour la verification
     * d'un paiement). En cas d'echec de l'appel, on retombe sur le dernier
     * taux connu avec succes (cache separe, sans expiration courte) plutot
     * que d'echouer immediatement - seule une toute premiere utilisation
     * sans jamais avoir reussi un appel echoue reellement.
     */
    public function eurUsdRate(): float
    {
        $cacheKey = 'exchange_rate.eur_usd';
        $lastKnownGoodKey = 'exchange_rate.eur_usd.last_known_good';

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (float) $cached;
        }

        try {
            $client = new Client(['timeout' => 8]);
            $response = $client->get(config('crypto.eur_usd_endpoint'));
            $decoded = json_decode((string) $response->getBody(), true);
            $rate = $decoded['rates']['USD'] ?? null;

            if (! is_numeric($rate)) {
                throw new RuntimeException('Reponse EUR/USD sans taux exploitable.');
            }

            Cache::put($cacheKey, (float) $rate, now()->addHour());
            Cache::put($lastKnownGoodKey, (float) $rate, now()->addDays(7));

            return (float) $rate;
        } catch (\Throwable $e) {
            Log::warning('ExchangeRate: appel EUR/USD echoue.', ['message' => $e->getMessage()]);

            $fallback = Cache::get($lastKnownGoodKey);
            if ($fallback !== null) {
                return (float) $fallback;
            }

            throw new RuntimeException('Cours EUR/USD indisponible et aucun dernier cours connu.', previous: $e);
        }
    }

    /**
     * Taux BNB/USD (essaie Binance, puis CoinGecko en repli), mis en cache
     * seulement 5 minutes : contrairement au change EUR/USD, le cours d'une
     * cryptomonnaie peut bouger sensiblement en quelques minutes. Aucun
     * repli sur un "dernier cours connu" ici : perime de plusieurs minutes,
     * un tel repli pourrait fausser significativement la verification d'un
     * paiement en BNB.
     */
    public function bnbUsdRate(): float
    {
        $cacheKey = 'exchange_rate.bnb_usd';

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (float) $cached;
        }

        $client = new Client(['timeout' => 8]);

        foreach (config('crypto.bnb_usd_endpoints', []) as $endpoint) {
            try {
                $response = $client->get($endpoint['url']);
                $decoded = json_decode((string) $response->getBody(), true);
                $rate = data_get($decoded, $endpoint['path']);

                if (! is_numeric($rate)) {
                    continue;
                }

                Cache::put($cacheKey, (float) $rate, now()->addMinutes(5));

                return (float) $rate;
            } catch (\Throwable $e) {
                Log::warning("ExchangeRate: appel BNB/USD echoue sur {$endpoint['url']}.", ['message' => $e->getMessage()]);

                continue;
            }
        }

        throw new RuntimeException('Cours BNB/USD indisponible (toutes les sources ont echoue).');
    }
}

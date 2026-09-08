<?php

namespace App\Services;

/**
 * Verification de la signature des webhooks FedaPay (point 15), sans le SDK
 * officiel (deja evite dans FedaPayClient pour ne pas ajouter de dependance
 * composer). Schema reconstitue depuis le code source public du SDK PHP
 * officiel (fedapay/fedapay-php, classe WebhookSignature) : en-tete
 * "X-FEDAPAY-SIGNATURE" au format "t=<horodatage>,s=<signature>", message
 * signe = "<horodatage>.<corps brut de la requete>", HMAC-SHA256 avec la
 * cle secrete du point de terminaison (distincte de la cle API secrete),
 * comparaison en temps constant. Jamais d'activation d'abonnement sans
 * signature valide : c'est cette verification qui protege contre un faux
 * webhook forge par un tiers.
 */
class FedaPaySignatureVerifier
{
    private const SCHEME = 's';

    /**
     * @throws \RuntimeException si la signature est absente, invalide, ou
     *                           l'horodatage hors tolerance (rejeu).
     */
    public static function verify(string $rawBody, ?string $header, string $secret, int $tolerance = 300): void
    {
        if (! $header) {
            throw new \RuntimeException('En-tete de signature FedaPay absent.');
        }

        $timestamp = self::extractTimestamp($header);
        $signatures = self::extractSignatures($header);

        if ($timestamp === null || $signatures === []) {
            throw new \RuntimeException('En-tete de signature FedaPay illisible.');
        }

        $expected = hash_hmac('sha256', "$timestamp.$rawBody", $secret);

        $matched = false;
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                $matched = true;
                break;
            }
        }

        if (! $matched) {
            throw new \RuntimeException('Signature FedaPay invalide.');
        }

        if ($tolerance > 0 && abs(time() - $timestamp) > $tolerance) {
            throw new \RuntimeException('Horodatage du webhook FedaPay hors tolerance (rejeu possible).');
        }
    }

    private static function extractTimestamp(string $header): ?int
    {
        foreach (explode(',', $header) as $item) {
            [$key, $value] = array_pad(explode('=', $item, 2), 2, null);
            if ($key === 't' && $value !== null && is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private static function extractSignatures(string $header): array
    {
        $signatures = [];
        foreach (explode(',', $header) as $item) {
            [$key, $value] = array_pad(explode('=', $item, 2), 2, null);
            if ($key === self::SCHEME && $value !== null) {
                $signatures[] = $value;
            }
        }

        return $signatures;
    }
}

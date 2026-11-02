<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Reglages de la plateforme modifiables depuis l'espace technique (cles
 * de l'assistant, de FedaPay, adresse crypto, contact). Une valeur saisie
 * ici l'emporte sur celle du fichier .env. Les secrets sont chiffres avec
 * la cle de l'application (APP_KEY) avant d'etre enregistres.
 */
class PlatformSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Champs gerables : cle => [libelle, chemin de config, secret ?]
     */
    public const FIELDS = [
        'anthropic_api_key' => ['Clé de l\'assistant Frère David', 'assistant.api_key', true],
        'anthropic_model' => ['Modèle de l\'assistant', 'assistant.model', false],
        'fedapay_environment' => ['FedaPay : mode (sandbox ou live)', 'fedapay.environment', false],
        'fedapay_public_key' => ['FedaPay : clé publique', 'fedapay.public_key', false],
        'fedapay_secret_key' => ['FedaPay : clé secrète', 'fedapay.secret_key', true],
        'fedapay_webhook_secret' => ['FedaPay : secret du webhook', 'fedapay.webhook_secret', true],
        'crypto_wallet_address' => ['Adresse de réception crypto (0x...)', 'crypto.wallet_address', false],
        'contact_email' => ['E-mail de contact (pages légales)', 'oikonema.contact_email', false],
    ];

    private const CACHE_KEY = 'platform_settings_values';

    /** @return array<string, string> valeurs en clair */
    public static function all_values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $out = [];
            foreach (static::query()->get() as $row) {
                $field = self::FIELDS[$row->key] ?? null;
                if (! $field || $row->value === null || $row->value === '') {
                    continue;
                }
                try {
                    $out[$row->key] = $field[2] ? Crypt::decryptString($row->value) : $row->value;
                } catch (Throwable) {
                    // Valeur illisible (APP_KEY changee) : ignoree, le .env reprend le relais.
                }
            }

            return $out;
        });
    }

    public static function put(string $key, ?string $value): void
    {
        $field = self::FIELDS[$key] ?? null;
        abort_unless($field, 422);

        if ($value === null || $value === '') {
            static::query()->where('key', $key)->delete();
        } else {
            static::query()->updateOrCreate(['key' => $key], ['value' => $field[2] ? Crypt::encryptString($value) : $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /** Applique les reglages enregistres sur la configuration de l'application. */
    public static function applyToConfig(): void
    {
        try {
            foreach (static::all_values() as $key => $value) {
                config([self::FIELDS[$key][1] => $value]);
            }
        } catch (Throwable) {
            // Table absente (avant migration) : on garde le .env.
        }
    }

    public static function mask(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return strlen($value) <= 8 ? '••••' : '••••'.substr($value, -4);
    }
}

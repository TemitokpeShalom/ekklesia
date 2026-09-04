<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Identifiants stables (uuid) sur tous les modeles - point 10, prepare
 * une future API mobile sans reconstruction : le meme identifiant vaut
 * pour le web d'aujourd'hui et l'API de demain.
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}

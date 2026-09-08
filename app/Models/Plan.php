<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalogue des offres d'abonnement (point 15) - table globale, comme
 * Role, jamais filtree par ministere.
 */
class Plan extends Model
{
    use HasUuid;

    public const DECOUVERTE = 'decouverte';
    public const ESSENTIEL = 'essentiel';
    public const CROISSANCE = 'croissance';

    protected $fillable = [
        'code', 'name', 'price_monthly', 'currency', 'max_members',
        'features', 'is_default', 'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'features' => 'array',
        'is_default' => 'boolean',
        'max_members' => 'integer',
        'sort_order' => 'integer',
    ];

    public function ministries(): HasMany
    {
        return $this->hasMany(Ministry::class);
    }
}

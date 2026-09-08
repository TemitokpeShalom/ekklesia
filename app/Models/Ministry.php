<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * L'annuaire des ministeres (point 03). Un ministere = un tenant = la
 * racine de son propre arbre org_units (point 01/02).
 */
class Ministry extends Model
{
    use HasUuid;

    // Titres proposes par defaut tant qu'aucune liste n'a ete configuree :
    // les cinq offices d'Ephesiens 4.11 puis les civilites courantes.
    // Reglage global au ministere (point 08, restant a l'architecture) -
    // voir HonorificTitlesController, reserve a la racine de l'arbre.
    public const DEFAULT_HONORIFIC_TITLES = [
        'Apôtre', 'Prophète', 'Évangéliste', 'Pasteur', 'Docteur', 'M.', 'Mme',
    ];

    protected $fillable = ['name', 'short_code', 'status', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function orgUnits(): HasMany
    {
        return $this->hasMany(OrgUnit::class);
    }

    public function root(): ?OrgUnit
    {
        return $this->orgUnits()->whereNull('parent_id')->first();
    }

    public function honorificTitles(): array
    {
        $configured = $this->settings['honorific_titles'] ?? null;

        return is_array($configured) && $configured !== []
            ? array_values($configured)
            : self::DEFAULT_HONORIFIC_TITLES;
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un noeud de l'arbre hierarchique, quel que soit son rang (point 01/02).
 * id est l'identite PERMANENTE : elle ne change jamais, meme apres une
 * transformation (point 13) - seuls name/level_rank/level_label/parent_id
 * changent, et chaque changement est trace dans OrgUnitHistory.
 */
class OrgUnit extends Model
{
    use HasUuid;

    // Rangs fixes (point 01) - le libelle affiche (level_label) est
    // configurable par ministere, ces constantes ne le sont jamais.
    public const RANK_MINISTERE = 0;
    public const RANK_CONTINENT = 1;
    public const RANK_PAYS = 2;
    public const RANK_REGION = 3;
    public const RANK_DISTRICT = 4;
    public const RANK_EGLISE_LOCALE = 5;
    public const RANK_CELLULE = 6;

    protected $fillable = [
        'ministry_id', 'parent_id', 'level_rank', 'level_label',
        'name', 'code', 'metadata', 'status', 'path',
    ];

    protected $casts = [
        'metadata' => 'array',
        'level_rank' => 'integer',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrgUnitHistory::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    /**
     * Tous les descendants (n'importe quelle profondeur), via le chemin
     * materialise - la requete qui alimente aussi bien la consolidation
     * (point 06) que la visibilite des annonces (point 07).
     */
    public function scopeDescendantsOf($query, self $node)
    {
        return $query->whereRaw('path <@ ?::ltree', [$node->path]);
    }
}

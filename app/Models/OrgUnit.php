<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Id est l'identite PERMANENTE : elle ne change jamais, meme apres une
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
     * Les membres (fideles) directement rattaches a ce noeud (typiquement
     * une eglise locale). Isolation par ministry_id (point 04), comme
     * toutes les tables multi-tenant.
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Les cultes (services) directement rattaches a ce noeud (typiquement
     * une eglise locale). Isolation par ministry_id (point 04), comme
     * toutes les tables multi-tenant.
     */
    public function cultes(): HasMany
    {
        return $this->hasMany(Culte::class);
    }

    /**
     * Les mouvements financiers (dimes, offrandes, actions de grace, dons,
     * depenses) directement rattaches a ce noeud. Isolation par
     * ministry_id (point 04), comme toutes les tables multi-tenant.
     */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * Les rapports d'activites mensuels (effectifs, baptemes, nouveaux
     * convertis) de ce noeud, toujours distincts du rapport financier
     * (point 18).
     */
    public function activityReports(): HasMany
    {
        return $this->hasMany(ActivityReport::class);
    }

    /**
     * Le registre des biens (immobiliers et mobiliers) directement
     * rattaches a ce noeud - fiche d'inventaire de fin d'annee (point 19).
     * Meme regle de consolidation « activite propre » que les effectifs
     * (point 06) et les finances (point 18) : jamais une nouvelle table
     * de hierarchie, seulement un nouveau registre.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
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

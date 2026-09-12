<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'first_name', 'last_name', 'title',
        'phone', 'email', 'gender', 'birth_date', 'joined_at',
        'status', 'metadata', 'photo_path', 'spouse_name', 'spouse_photo_path',
    ];

    protected $casts = [
        'metadata' => 'array',
        'birth_date' => 'date',
        'joined_at' => 'date',
    ];

    public function ministry(): BelongsTo { return $this->belongsTo(Ministry::class); }
    public function orgUnit(): BelongsTo { return $this->belongsTo(OrgUnit::class); }

    /**
     * Corrige le 2026-09-12 (retour du ministere) : fiche membre minimale
     * creee a la volee quand un ecran (Parcours de disciple, Equipes...)
     * permet de "renseigner le nom" d'une personne qui n'est pas encore
     * enregistree, plutot que de la laisser comme un simple nom libre
     * orphelin. Prenom/nom est un simple decoupage du nom saisi -
     * completable ensuite depuis le module Membres, qui reste la source
     * complete des fiches (telephone, photo, etc.). Factorise ici pour que
     * tous les ecrans concernes partagent exactement la meme regle.
     */
    public static function createMinimal(OrgUnit $orgUnit, string $fullName): self
    {
        $fullName = trim($fullName);
        $parts = preg_split('/\s+/', $fullName, 2);

        return $orgUnit->members()->create([
            'ministry_id' => $orgUnit->ministry_id,
            'first_name' => $parts[0] ?? $fullName,
            'last_name' => $parts[1] ?? '',
            'status' => 'active',
        ]);
    }

    /**
     * Toutes les etapes de croissance spirituelle franchies par ce membre
     * (point 08), du plus recent au plus ancien - jamais une colonne
     * "etape actuelle" a synchroniser separement.
     */
    public function discipleshipStages(): HasMany
    {
        return $this->hasMany(DiscipleshipStage::class)->orderByDesc('reached_at');
    }

    /**
     * L'etape la plus recente atteinte par ce membre, calculee directement
     * en base (latestOfMany) plutot que par un champ duplique : la source
     * de verite reste toujours le journal complet dans discipleshipStages().
     */
    public function latestDiscipleshipStage(): HasOne
    {
        return $this->hasOne(DiscipleshipStage::class)->latestOfMany('reached_at');
    }
}

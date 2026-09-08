<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Canal de signalement (point 17) : une preoccupation remontee depuis un
 * noeud vers sa hierarchie. submitted_by reste nul quand is_anonymous est
 * vrai - l'anonymat porte sur la donnee elle-meme, pas seulement sur son
 * affichage (voir SignalementsController::store).
 */
class Signalement extends Model
{
    use HasUuid;

    public const STATUT_NOUVEAU = 'nouveau';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_TRAITE = 'traite';

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'category', 'message',
        'is_anonymous', 'submitted_by', 'status',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}

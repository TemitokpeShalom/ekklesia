<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'name', 'description', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    /**
     * Les affectations de membres a cette equipe (point 08). Le membre lui
     * meme s'obtient via $teamMember->member, jamais par une relation
     * many-to-many directe : cela garde le role dans l'equipe et la date
     * d'entree visibles sans jointure supplementaire.
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class)->orderBy('joined_at');
    }
}

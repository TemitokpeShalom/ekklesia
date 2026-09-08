<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscipleshipStage extends Model
{
    use HasUuid;

    /**
     * Etapes fixes du parcours de disciple (point 08). Volontairement non
     * configurables par ministere (contrairement aux titres honorifiques) :
     * ce sont des jalons universels de croissance, pas une convention
     * culturelle locale.
     */
    public const STAGES = [
        'nouveau_converti' => 'Nouveau converti',
        'baptise' => 'Baptisé',
        'en_formation' => 'En formation',
        'engage_service' => 'Engagé dans le service',
        'envoye_leader' => 'Envoyé / leader',
    ];

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'member_id', 'stage', 'reached_at', 'notes', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'reached_at' => 'date',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}

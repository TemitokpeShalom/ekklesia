<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityReport extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'period',
        'baptisms_count', 'new_converts_count',
        'activities_notes', 'remarks', 'leader_notes', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'period' => 'date',
        'baptisms_count' => 'integer',
        'new_converts_count' => 'integer',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }
}

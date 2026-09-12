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
        'validated_at', 'validated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'period' => 'date',
        'baptisms_count' => 'integer',
        'new_converts_count' => 'integer',
        'validated_at' => 'datetime',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Chantier "module Documents" (2026-09-12) : une fois valide, le
    // rapport passe en lecture seule (voir ActivityReportController) - il
    // peut alors etre archive/imprime depuis Documents > Rapports.
    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }
}

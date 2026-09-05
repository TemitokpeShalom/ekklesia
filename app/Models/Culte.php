<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Culte extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'title', 'service_date',
        'start_time', 'speaker', 'key_verses', 'attendance_adults', 'attendance_children',
        'notes', 'status', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'service_date' => 'date',
        'attendance_adults' => 'integer',
        'attendance_children' => 'integer',
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

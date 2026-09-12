<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sacrament extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'type', 'member_id', 'member_name', 'spouse_member_id',
        'spouse_name', 'event_date', 'officiant', 'location', 'notes', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'event_date' => 'date',
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

    public function spouseMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'spouse_member_id');
    }
}

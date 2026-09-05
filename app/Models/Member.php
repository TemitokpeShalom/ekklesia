<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'first_name', 'last_name',
        'phone', 'email', 'gender', 'birth_date', 'joined_at',
        'status', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'birth_date' => 'date',
        'joined_at' => 'date',
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

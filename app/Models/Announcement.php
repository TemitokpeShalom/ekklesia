<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'author_id', 'title', 'body',
        'attachment_path', 'attachment_original_name', 'attachment_mime',
        'important', 'expires_at', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'important' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }
}

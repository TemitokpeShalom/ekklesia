<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Fil de discussion entre une personne et l'equipe technique (un par personne). */
class SupportThread extends Model
{
    use HasUuid;

    protected $fillable = ['user_id', 'ministry_id', 'status', 'unread_by_staff', 'unread_by_user', 'last_message_at'];

    protected $casts = [
        'unread_by_staff' => 'boolean',
        'unread_by_user' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'thread_id')->orderBy('created_at');
    }
}

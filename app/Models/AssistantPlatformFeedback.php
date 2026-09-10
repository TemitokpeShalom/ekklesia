<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Voir la migration create_assistant_platform_feedback_table pour le
 * raisonnement complet : canal SANS RLS, destine a Martin seul, jamais
 * expose par une route applicative.
 */
class AssistantPlatformFeedback extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'user_id', 'conversation_id', 'category', 'message', 'status',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

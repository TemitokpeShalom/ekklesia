<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantMessage extends Model
{
    use HasUuid;

    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    protected $fillable = [
        'ministry_id', 'conversation_id', 'role', 'content', 'attachments',
        'input_tokens', 'output_tokens', 'error',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversation::class, 'conversation_id');
    }
}

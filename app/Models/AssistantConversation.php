<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un fil de discussion avec l'assistant IA (voir AssistantMessage). Prive a
 * son user_id : la RLS isole par ministere (point 04), mais deux personnes
 * du meme ministere ne doivent pas se lire l'une l'autre pour autant -
 * toute requete applicative doit donc TOUJOURS filtrer explicitement par
 * user_id en plus (voir AssistantService), la RLS ne le fait pas a elle
 * seule ici.
 */
class AssistantConversation extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'user_id', 'org_unit_id', 'title', 'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AssistantMessage::class, 'conversation_id')->orderBy('created_at');
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Le lien DATE Organisation <-> Utilisateur <-> Role (point 11) : le
 * troisieme des quatre objets distincts de l'architecture. Une affectation
 * revoquee n'est jamais supprimee (point 16 - continuite des acces).
 */
class Affectation extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'user_id', 'org_unit_id', 'role_id', 'status',
        'started_at', 'ended_at', 'assigned_by', 'revoked_by', 'revocation_reason',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}

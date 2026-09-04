<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Code de rattachement (point 03) : autorise la creation d'UN nouveau
 * noeud, sous le noeud emetteur, au rang attendu. La consommation du code
 * fait tout le travail d'heritage (parent_id, path, ministry_id) - voir
 * AttachmentCodeService.
 */
class AttachmentCode extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'issuing_org_unit_id', 'target_level_rank', 'code_hash',
        'status', 'issued_by', 'expires_at', 'used_by', 'used_at', 'created_org_unit_id',
    ];

    protected $hidden = ['code_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'target_level_rank' => 'integer',
    ];

    public function issuingOrgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class, 'issuing_org_unit_id');
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at->isPast();
    }
}

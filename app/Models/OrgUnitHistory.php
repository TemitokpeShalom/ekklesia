<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne = un etat passe (ou courant) d'un OrgUnit, date (point 02/13).
 * Jamais modifiee une fois valid_to renseigne : seule une nouvelle
 * transformation ferme la ligne en cours et en ouvre une autre.
 */
class OrgUnitHistory extends Model
{
    use HasUuid;

    protected $table = 'org_unit_history';

    public const UPDATED_AT = null; // immuable une fois cree (sauf cloture de valid_to par le service)

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'valid_from', 'valid_to',
        'name', 'level_rank', 'level_label', 'parent_id', 'path',
        'transformation_type', 'requested_by', 'approved_by', 'reason',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'level_rank' => 'integer',
    ];

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

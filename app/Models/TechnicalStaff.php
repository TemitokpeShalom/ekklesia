<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Appartenance a l'equipe technique Oikonema (voir la migration de
 * creation pour le contexte complet). user_id est unique : une personne
 * appartient a l'equipe technique ou non, jamais "plus ou moins".
 */
class TechnicalStaff extends Model
{
    use HasUuid;

    protected $table = 'technical_staff';

    protected $fillable = ['user_id', 'added_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}

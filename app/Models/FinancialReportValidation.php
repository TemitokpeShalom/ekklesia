<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marque qu'un responsable a verifie et archive le rapport financier d'un
 * mois donne, pour un OrgUnit donne (voir migration
 * 2026_09_12_000002_create_financial_report_validations_table). Le rapport
 * financier lui-meme n'est jamais stocke ici - seule cette attestation
 * l'est ; les mouvements du mois restent modifiables (voir
 * FinanceReportController).
 */
class FinancialReportValidation extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'period', 'validated_at', 'validated_by',
    ];

    protected $casts = [
        'period' => 'date',
        'validated_at' => 'datetime',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}

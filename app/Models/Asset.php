<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'category', 'code', 'label',
        'quantity', 'acquisition_date', 'acquisition_value', 'currency',
        'provenance', 'financial_transaction_id', 'condition',
        'observation', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'acquisition_date' => 'date',
        'acquisition_value' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }
}

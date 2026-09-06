<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'type', 'nature',
        'account_code', 'account_label', 'amount', 'currency',
        'transaction_date', 'counterparty', 'description',
        'recorded_by', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnit::class);
    }
}

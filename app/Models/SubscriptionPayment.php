<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Point 15 : une ligne par tentative de paiement d'abonnement (FedaPay ou
 * crypto), voir la migration create_subscription_payments_table pour le
 * detail. L'etat courant de l'abonnement reste sur Ministry ; cette table
 * n'est qu'un historique, jamais relue pour decider si l'acces est actif.
 */
class SubscriptionPayment extends Model
{
    use HasUuid;

    public const PROVIDER_FEDAPAY = 'fedapay';

    public const PROVIDER_CRYPTO = 'crypto';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'ministry_id', 'plan_id', 'provider', 'status',
        'amount', 'currency', 'provider_reference', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:8',
    ];

    public function ministry(): BelongsTo
    {
        return $this->belongsTo(Ministry::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}

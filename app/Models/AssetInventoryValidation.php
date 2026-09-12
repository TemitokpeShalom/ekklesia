<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marque qu'un responsable a verifie et archive la fiche d'inventaire
 * d'une annee donnee, pour un OrgUnit donne (voir migration
 * 2026_09_12_000007_create_asset_inventory_validations_table). Meme
 * principe que FinancialReportValidation : la fiche elle-meme n'est
 * jamais stockee ici, seule cette attestation l'est - le registre des
 * biens (voir AssetsController::rapport) reste la seule source de verite.
 */
class AssetInventoryValidation extends Model
{
    use HasUuid;

    protected $fillable = [
        'ministry_id', 'org_unit_id', 'year', 'validated_at', 'validated_by',
    ];

    protected $casts = [
        'year' => 'integer',
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

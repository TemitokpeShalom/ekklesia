<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * L'annuaire des ministeres (point 03). Un ministere = un tenant = la
 * racine de son propre arbre org_units (point 01/02).
 */
class Ministry extends Model
{
    use HasUuid;

    protected $fillable = ['name', 'short_code', 'status'];

    public function orgUnits(): HasMany
    {
        return $this->hasMany(OrgUnit::class);
    }

    public function root(): ?OrgUnit
    {
        return $this->orgUnits()->whereNull('parent_id')->first();
    }
}

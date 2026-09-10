<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalogue des offres d'abonnement (point 15) - table globale, comme
 * Role, jamais filtree par ministere.
 *
 * Correction du 2026-09-10 : le palier se mesure au nombre d'EGLISES
 * LOCALES rattachees (max_local_churches), plus au nombre de membres -
 * une limite sur les membres inciterait a un moment ou un autre a freiner
 * leur enregistrement, l'inverse de ce que la plateforme doit encourager.
 * Le palier National a un prix fixe (50 000 FCFA, choisi bas au lancement
 * pour attirer les premiers grands ministeres - voir PlanSeeder) et aucun
 * plafond d'eglises (max_local_churches null =
 * illimite). price_monthly reste nullable au niveau du schema pour un
 * futur palier reellement "sur devis" si besoin : dans ce cas precis,
 * null masque automatiquement les boutons de paiement en ligne et affiche
 * "Sur devis" (voir Abonnement.vue).
 */
class Plan extends Model
{
    use HasUuid;

    public const DECOUVERTE = 'decouverte';
    public const ESSENTIEL = 'essentiel';
    public const CROISSANCE = 'croissance';
    public const NATIONAL = 'national';

    protected $fillable = [
        'code', 'name', 'price_monthly', 'currency', 'max_local_churches',
        'features', 'is_default', 'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'features' => 'array',
        'is_default' => 'boolean',
        'max_local_churches' => 'integer',
        'sort_order' => 'integer',
    ];

    public function ministries(): HasMany
    {
        return $this->hasMany(Ministry::class);
    }
}

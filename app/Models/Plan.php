<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalogue des offres d'abonnement (point 15) - table globale, comme
 * Role, jamais filtree par ministere.
 *
 * Correction du 2026-09-10 : le palier se mesure au nombre d'entites
 * organisationnelles rattachees, plus au nombre de membres - une limite
 * sur les membres inciterait a un moment ou un autre a freiner leur
 * enregistrement, l'inverse de ce que la plateforme doit encourager.
 *
 * Correction du 2026-09-12 (retour du ministere : "un noeud peut aussi
 * etre une eglise... si les gens comprennent que c'est le mot 'eglise
 * locale' qui decompte, ils vont tout creer en district pour contourner
 * ca") : max_local_churches renomme max_org_units - le plafond compte
 * DESORMAIS chaque entite creee, tous niveaux confondus (continent/pays/
 * region/district/eglise locale/cellule), jamais seulement les eglises
 * locales - voir Ministry::assertCanCreateOrgUnit().
 *
 * Le palier National a un prix fixe (choisi bas au lancement pour attirer
 * les premiers grands ministeres - voir PlanSeeder) et aucun plafond
 * (max_org_units null = illimite). price_monthly reste nullable au niveau
 * du schema pour un futur palier reellement "sur devis" si besoin : dans
 * ce cas precis, null masque automatiquement les boutons de paiement en
 * ligne et affiche "Sur devis" (voir Abonnement.vue).
 */
class Plan extends Model
{
    use HasUuid;

    public const DECOUVERTE = 'decouverte';
    public const ESSENTIEL = 'essentiel';
    public const CROISSANCE = 'croissance';
    public const NATIONAL = 'national';

    protected $fillable = [
        'code', 'name', 'price_monthly', 'currency', 'max_org_units',
        'features', 'is_default', 'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'features' => 'array',
        'is_default' => 'boolean',
        'max_org_units' => 'integer',
        'sort_order' => 'integer',
    ];

    public function ministries(): HasMany
    {
        return $this->hasMany(Ministry::class);
    }
}

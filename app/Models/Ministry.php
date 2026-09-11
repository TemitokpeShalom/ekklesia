<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * L'annuaire des ministeres (point 03). Un ministere = un tenant = la
 * racine de son propre arbre org_units (point 01/02).
 */
class Ministry extends Model
{
    use HasUuid;

    // Titres proposes par defaut tant qu'aucune liste n'a ete configuree :
    // les cinq offices d'Ephesiens 4.11 puis les civilites courantes.
    // Reglage global au ministere (point 08, restant a l'architecture) -
    // voir HonorificTitlesController, reserve a la racine de l'arbre.
    public const DEFAULT_HONORIFIC_TITLES = [
        'Apôtre', 'Prophète', 'Évangéliste', 'Pasteur', 'Docteur', 'M.', 'Mme',
    ];

    // Duree de l'essai gratuit a la creation du ministere (point 15).
    public const TRIAL_DAYS = 30;

    protected $fillable = [
        'name', 'short_code', 'status', 'settings',
        'plan_id', 'subscription_status', 'trial_ends_at', 'current_period_ends_at',
        // Identite officielle (2026-09-09) : ce que porte un dossier de
        // reconnaissance de culte aupres du Ministere de l'Interieur et de
        // la Securite Publique (nom, sigle, siege, coordonnees, numero
        // d'autorisation), plus le logo. Tous nullables - voir la migration
        // 2026_09_10_000007 pour le detail.
        'acronym', 'registration_number', 'headquarters_address',
        'phone', 'email', 'website', 'logo_path',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'current_period_ends_at' => 'datetime',
    ];

    public function orgUnits(): HasMany
    {
        return $this->hasMany(OrgUnit::class);
    }

    // Point 15 : seule voie de lecture pour subscription_payments (table
    // volontairement sans RLS, voir sa migration) - toujours en passant par
    // ce ministere, jamais une requete libre sur la table.
    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function root(): ?OrgUnit
    {
        return $this->orgUnits()->whereNull('parent_id')->first();
    }

    public function honorificTitles(): array
    {
        $configured = $this->settings['honorific_titles'] ?? null;

        return is_array($configured) && $configured !== []
            ? array_values($configured)
            : self::DEFAULT_HONORIFIC_TITLES;
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // Point 15 : tant que l'essai n'est pas expire, l'acces reste complet
    // meme sans offre payante choisie - c'est onTrial() qui l'autorise,
    // pas la presence d'un plan_id.
    public function onTrial(): bool
    {
        return $this->subscription_status === 'essai'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    public function subscriptionActive(): bool
    {
        return $this->onTrial()
            || ($this->subscription_status === 'active'
                && ($this->current_period_ends_at === null || $this->current_period_ends_at->isFuture()));
    }

    /**
     * En-tête officiel du ministère (2026-09-11) : demande explicite du
     * ministère - le nom, le sigle, le n° d'autorisation, l'adresse, les
     * coordonnées et le logo saisis via Settings/MinistryInfo doivent
     * pouvoir s'afficher, bien centrés, en haut de l'espace de travail
     * (Dashboard) et de chaque document généré (rapport financier, rapport
     * d'activités) - comme un en-tête de courrier officiel. Toutes les
     * valeurs sont nullables : un ministère qui n'a encore rien renseigné
     * (ex. le compte de démonstration) obtient un en-tête minimal (le nom
     * seul), jamais une page cassée - voir MinistryLetterhead.vue, qui
     * consomme exactement cette forme.
     */
    public function letterhead(): array
    {
        return [
            'name' => $this->name,
            'acronym' => $this->acronym,
            'registration_number' => $this->registration_number,
            'headquarters_address' => $this->headquarters_address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'logo_url' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
        ];
    }
}

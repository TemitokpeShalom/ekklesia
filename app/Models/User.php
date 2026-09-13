<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Une personne = UN SEUL compte (point 11). Ses postes (eventuellement
 * plusieurs, sur des noeuds differents) vivent dans ses Affectations,
 * jamais en dupliquant ce compte.
 */
class User extends Authenticatable
{
    use HasFactory, HasUuid, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function activeAffectations(): HasMany
    {
        return $this->affectations()->where('status', 'active');
    }

    /**
     * Bibliotheque ministerielle (point 08) : verification a plat, pas
     * en cascade comme OrgUnitPolicy - un Pasteur de cellule y a acces
     * meme si aucune affectation ne couvre l'ensemble du ministere.
     */
    public function hasPreachingAffectation(): bool
    {
        return $this->activeAffectations()
            ->whereHas('role', fn ($q) => $q->where('can_preach', true))
            ->exists();
    }

    /**
     * Equipe technique Oikonema (2026-09-13) : appartenance PLATEFORME,
     * jamais liee a un ministere - a ne pas confondre avec
     * Role::ADMIN_TECHNIQUE (fonction interne A un ministere, rattachee via
     * une Affectation). Vrai si un enregistrement technical_staff existe
     * pour ce compte, OU si son e-mail figure dans TECHNICAL_STAFF_EMAILS
     * (.env, voir config/oikonema.php) - cette seconde voie amorce le tout
     * premier membre, qui ne pourrait sinon jamais s'ajouter lui-meme depuis
     * un ecran reserve... a l'equipe technique.
     */
    public function isTechnicalStaff(): bool
    {
        if (TechnicalStaff::where('user_id', $this->id)->exists()) {
            return true;
        }

        $emails = array_map('strtolower', config('oikonema.technical_staff_emails', []));

        return in_array(strtolower($this->email), $emails, true);
    }
}

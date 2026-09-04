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
}

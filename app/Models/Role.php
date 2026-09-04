<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Catalogue UNIQUE de fonctions (point 05) : Pasteur, Secretaire general,
 * Tresorier, Comptable + adjoints, administrateur technique. Un role ne
 * se duplique jamais par niveau - il se rattache a un noeud via une
 * Affectation.
 */
class Role extends Model
{
    use HasUuid;

    public const PASTEUR = 'pasteur';
    public const PASTEUR_ADJOINT = 'pasteur_adjoint';
    public const SECRETAIRE_GENERAL = 'secretaire_general';
    public const SECRETAIRE_ADJOINT = 'secretaire_adjoint';
    public const TRESORIER = 'tresorier';
    public const TRESORIER_ADJOINT = 'tresorier_adjoint';
    public const COMPTABLE = 'comptable';
    public const COMPTABLE_ADJOINT = 'comptable_adjoint';
    public const ADMIN_TECHNIQUE = 'admin_technique';

    protected $fillable = ['code', 'label', 'is_deputy', 'default_permissions', 'can_manage_users'];

    protected $casts = [
        'default_permissions' => 'array',
        'is_deputy' => 'boolean',
        'can_manage_users' => 'boolean',
    ];
}

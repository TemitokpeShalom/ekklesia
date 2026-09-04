<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Le catalogue de fonctions demande explicitement (point 05) : Pasteur,
 * Pasteur adjoint, Secretaire general, Secretaire adjoint, Tresorier,
 * Tresorier adjoint, Comptable, Comptable adjoint, Administrateur
 * technique.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [Role::PASTEUR, 'Pasteur', false, true],
            [Role::PASTEUR_ADJOINT, 'Pasteur adjoint', true, true],
            [Role::SECRETAIRE_GENERAL, 'Secrétaire général', false, false],
            [Role::SECRETAIRE_ADJOINT, 'Secrétaire adjoint', true, false],
            [Role::TRESORIER, 'Trésorier', false, false],
            [Role::TRESORIER_ADJOINT, 'Trésorier adjoint', true, false],
            [Role::COMPTABLE, 'Comptable', false, false],
            [Role::COMPTABLE_ADJOINT, 'Comptable adjoint', true, false],
            [Role::ADMIN_TECHNIQUE, 'Administrateur technique', false, true],
        ];

        foreach ($roles as [$code, $label, $isDeputy, $canManageUsers]) {
            Role::updateOrCreate(['code' => $code], [
                'label' => $label,
                'is_deputy' => $isDeputy,
                'can_manage_users' => $canManageUsers,
                'default_permissions' => [
                    'saisie' => true,
                    'validation' => in_array($code, [Role::PASTEUR, Role::SECRETAIRE_GENERAL]),
                    'finances' => in_array($code, [Role::TRESORIER, Role::TRESORIER_ADJOINT, Role::COMPTABLE, Role::COMPTABLE_ADJOINT, Role::PASTEUR]),
                    'annonces' => in_array($code, [Role::PASTEUR, Role::SECRETAIRE_GENERAL]),
                    'gestion_acces' => $canManageUsers,
                ],
            ]);
        }
    }
}

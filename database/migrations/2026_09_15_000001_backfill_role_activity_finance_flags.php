<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige le 2026-09-15 (retour du ministere : apres la migration du
 * 2026-09-14 qui a ajoute can_manage_activities/can_manage_finances, le
 * secretaire general n'avait TOUJOURS acces a rien) : cette migration
 * precedente ajoutait les deux colonnes mais les laissait a leur valeur par
 * defaut (false) pour TOUS les roles existants - remplir les bonnes
 * valeurs par role dependait d'une deuxieme commande separee
 * (php artisan db:seed --class=RoleSeeder), une etape manuelle facile a
 * oublier ou a manquer, surtout juste apres une autre livraison avec des
 * commandes differentes. Cette migration inscrit desormais directement les
 * bonnes valeurs par role EN MEME TEMPS que la migration (donc un simple
 * `php artisan migrate` suffit desormais, plus besoin de retenir une
 * commande a part) - les memes valeurs que RoleSeeder (garde en place, sans
 * changement, pour les nouvelles installations).
 */
return new class extends Migration
{
    public function up(): void
    {
        $values = [
            Role::PASTEUR => [true, true],
            Role::PASTEUR_ADJOINT => [true, true],
            Role::SECRETAIRE_GENERAL => [true, false],
            Role::SECRETAIRE_ADJOINT => [true, false],
            Role::TRESORIER => [false, true],
            Role::TRESORIER_ADJOINT => [false, true],
            Role::COMPTABLE => [false, true],
            Role::COMPTABLE_ADJOINT => [false, true],
            Role::ADMIN_TECHNIQUE => [true, true],
        ];

        foreach ($values as $code => [$canManageActivities, $canManageFinances]) {
            DB::table('roles')->where('code', $code)->update([
                'can_manage_activities' => $canManageActivities,
                'can_manage_finances' => $canManageFinances,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->update([
            'can_manage_activities' => false,
            'can_manage_finances' => false,
        ]);
    }
};

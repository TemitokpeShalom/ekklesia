<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Ministry;
use App\Models\OrgUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creation libre-service d'un tout nouveau ministere (tenant), ajoutee le
 * 2026-09-09 : second parcours de la page d'accueil (voir WelcomeController
 * et MinistryRegistrationController), a cote de "se connecter à mon espace
 * de travail". Comportement choisi par l'utilisateur : creation immediate,
 * sans validation manuelle - comme pour une invitation ou un rattachement,
 * personne ne relit ni n'approuve cette creation. La personne qui remplit
 * ce formulaire devient titulaire (role Pasteur, meme role que pour un
 * rattachement) de la racine du nouveau ministere, avec l'essai gratuit
 * de Ministry::TRIAL_DAYS demarrant immediatement (meme reglage que
 * partout ailleurs - voir Ministry::onTrial()).
 *
 * Structure calquee sur les deux seuls autres endroits du code qui creent
 * un ministere/une racine ou un compte + affectation "depuis zero" :
 * DemoMinistrySeedCommand (racine d'un ministere) et
 * AttachmentCodeService::consume() / InvitationService::accept() (compte
 * fondateur + affectation).
 */
class MinistryRegistrationService
{
    /**
     * @return array{0: Ministry, 1: OrgUnit, 2: User}
     */
    public function register(array $ministryAttributes, array $accountAttributes): array
    {
        return DB::transaction(function () use ($ministryAttributes, $accountAttributes) {
            $ministry = Ministry::create([
                'name' => $ministryAttributes['name'],
                'short_code' => $this->uniqueShortCode($ministryAttributes['short_code'] ?? $ministryAttributes['name']),
                'status' => 'active',
                'subscription_status' => 'essai',
                'trial_ends_at' => now()->addDays(Ministry::TRIAL_DAYS),
            ]);

            // Meme raison que DemoMinistrySeedCommand et tous les points
            // d'entree "sans compte" (point 04) : cette ecriture precede la
            // creation meme du compte qui la justifierait, donc
            // app.current_ministry_id doit etre fixe explicitement avant
            // toute ecriture sur les tables multi-tenant (org_units,
            // org_unit_history, affectations) - sinon la policy RLS "fail
            // closed" les rejette silencieusement. La table ministries
            // elle-meme n'a pas de RLS (voir 2026_09_02_000009_enable_row_
            // level_security.php), d'ou la creation du ministere juste
            // au-dessus, avant ce SET LOCAL.
            DB::statement("SET LOCAL app.current_ministry_id = '{$ministry->id}'");

            $rootCode = Str::slug($ministry->short_code, '_') ?: 'ministere';

            $root = OrgUnit::create([
                'ministry_id' => $ministry->id,
                'parent_id' => null,
                'level_rank' => OrgUnit::RANK_MINISTERE,
                'level_label' => 'Ministère',
                'name' => $ministry->name,
                'code' => $rootCode,
                'metadata' => [],
                'status' => 'active',
                'path' => $rootCode,
            ]);

            $user = User::create([
                'name' => $accountAttributes['name'],
                'email' => $accountAttributes['email'],
                'phone' => $accountAttributes['phone'] ?? null,
                'password' => $accountAttributes['password'],
                'status' => 'active',
            ]);

            $root->history()->create([
                'ministry_id' => $ministry->id,
                'valid_from' => now()->toDateString(),
                'valid_to' => null,
                'name' => $root->name,
                'level_rank' => $root->level_rank,
                'level_label' => $root->level_label,
                'parent_id' => null,
                'path' => $root->path,
                'transformation_type' => 'creation',
                'requested_by' => $user->id,
                'approved_by' => $user->id,
                'reason' => 'Création du ministère via inscription libre-service.',
            ]);

            // Sans cette affectation, la personne qui vient de creer son
            // ministere n'a aucun droit dessus (OrgUnitPolicy::view exige
            // une affectation active) et se heurte a un refus d'acces
            // immediatement apres avoir "reussi" a le creer - meme raison
            // que dans AttachmentCodeService::consume().
            Affectation::create([
                'ministry_id' => $ministry->id,
                'user_id' => $user->id,
                'org_unit_id' => $root->id,
                'role_id' => Role::where('code', Role::PASTEUR)->firstOrFail()->id,
                'status' => 'active',
                'started_at' => now()->toDateString(),
            ]);

            return [$ministry, $root, $user];
        });
    }

    /**
     * short_code doit etre unique globalement (contrainte ministries.
     * short_code, voir sa migration) - une personne qui remplit ce
     * formulaire ne connait evidemment pas les codes deja pris : on ajoute
     * un suffixe plutot que de faire echouer l'inscription sur une
     * contrainte SQL illisible. Meme logique que
     * AttachmentCodeService::uniqueChildCode(), a l'echelle de tous les
     * ministeres plutot que des enfants d'un seul parent.
     */
    private function uniqueShortCode(string $desired): string
    {
        $base = Str::upper(Str::slug($desired, '-')) ?: 'MINISTERE';
        $code = $base;
        $suffix = 1;

        while (Ministry::where('short_code', $code)->exists()) {
            $suffix++;
            $code = "{$base}-{$suffix}";
        }

        return $code;
    }
}

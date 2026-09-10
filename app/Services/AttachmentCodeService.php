<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\AttachmentCode;
use App\Models\OrgUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Le mecanisme de rattachement par code (point 03) : un niveau superieur
 * autorise la creation d'un noeud sans jamais laisser choisir un parent
 * librement. Utilise depuis un controleur HTTP aujourd'hui, et pourra
 * demain etre appele a l'identique depuis une API mobile (point 10) - la
 * logique ne change pas, seule la couche de presentation change.
 */
class AttachmentCodeService
{
    /**
     * Emet un code a usage unique pour un niveau superieur donne.
     * Seul un compte disposant d'une affectation active habilitee sur ce
     * noeud peut en emettre un (verifie par la policy du controleur, pas
     * ici : cette classe ne connait pas la notion de "requete HTTP").
     */
    public function issue(OrgUnit $issuingOrgUnit, int $targetLevelRank, User $issuedBy, int $validForHours = 72): array
    {
        if ($targetLevelRank <= $issuingOrgUnit->level_rank) {
            throw new RuntimeException(
                "Un code emis par le rang {$issuingOrgUnit->level_rank} ne peut créer qu'un rang inférieur "
                .'(un district ne peut pas créer un autre district ou une région).'
            );
        }

        $plainCode = Str::upper(Str::random(4).'-'.Str::random(4));

        $attachmentCode = AttachmentCode::create([
            'ministry_id' => $issuingOrgUnit->ministry_id,
            'issuing_org_unit_id' => $issuingOrgUnit->id,
            'target_level_rank' => $targetLevelRank,
            'code_hash' => Hash::make($plainCode),
            'status' => 'pending',
            'issued_by' => $issuedBy->id,
            'expires_at' => now()->addHours($validForHours),
        ]);

        // Le code en clair n'est jamais stocke : il n'existe qu'ici, le
        // temps de le transmettre a la personne qui va l'utiliser.
        return [$attachmentCode, $plainCode];
    }

    /**
     * Retrouve un code de rattachement pendant/valide a partir de sa valeur
     * en clair, sans le consommer - utilise pour afficher a l'avance a
     * quel niveau la personne qui saisit le code va creer une entite,
     * avant qu'elle ne remplisse le reste du formulaire (point 03).
     */
    public function peek(string $plainCode): ?AttachmentCode
    {
        return AttachmentCode::where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get()
            ->first(fn (AttachmentCode $candidate) => Hash::check($plainCode, $candidate->code_hash));
    }

    /**
     * Consomme un code pour creer le nouveau noeud. parent_id, path et
     * ministry_id sont TOUJOURS herites du noeud emetteur - jamais saisis
     * par la personne qui remplit le formulaire (point 03).
     *
     * $usedBy est la personne qui redeem le code si elle a deja un compte
     * Oikonema (ex. un responsable regional qui rattache lui-meme une
     * nouvelle entite a sa propre branche). Si elle n'en a pas encore -
     * cas normal d'une eglise reellement nouvelle - $usedBy est null et
     * $newAccount doit porter les champs necessaires a la creation d'un
     * compte (name/email/phone/password, deja hache) : ce compte devient
     * le titulaire (role Pasteur) de la nouvelle entite.
     *
     * @return array{0: OrgUnit, 1: User}
     */
    public function consume(string $plainCode, array $newUnitAttributes, ?User $usedBy, ?array $newAccount = null): array
    {
        if (! $usedBy && ! $newAccount) {
            throw new RuntimeException('Un compte existant ou les informations pour en créer un sont nécessaires.');
        }

        $candidates = AttachmentCode::where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get();

        $attachmentCode = $candidates->first(
            fn (AttachmentCode $candidate) => Hash::check($plainCode, $candidate->code_hash)
        );

        if (! $attachmentCode) {
            throw new RuntimeException('Code de rattachement invalide, déjà utilisé ou expiré.');
        }

        return DB::transaction(function () use ($attachmentCode, $newUnitAttributes, $usedBy, $newAccount) {
            $issuingUnit = $attachmentCode->issuingOrgUnit;

            // Ce transaction s'execute forcement hors du middleware
            // tenant.context (point 04) : que la personne qui redeem ait
            // deja une session ou non, cette requete precede toute
            // connexion pour une eglise reellement nouvelle. Sans ce
            // SET LOCAL, les policies RLS "fail closed" des tables
            // multi-tenant (org_units, org_unit_history, affectations)
            // rejetteraient silencieusement chaque ecriture ci-dessous -
            // meme technique que DemoMinistrySeedCommand pour la meme
            // raison (une commande artisan ne passe pas non plus par le
            // middleware web).
            DB::statement("SET LOCAL app.current_ministry_id = '{$issuingUnit->ministry_id}'");

            if (! $usedBy) {
                $usedBy = User::firstWhere('email', $newAccount['email']) ?? User::create([
                    'name' => $newAccount['name'],
                    'email' => $newAccount['email'],
                    'phone' => $newAccount['phone'] ?? null,
                    'password' => $newAccount['password'],
                    'status' => 'active',
                ]);
            }

            $code = $this->uniqueChildCode($issuingUnit, $newUnitAttributes['code']);

            $newUnit = OrgUnit::create([
                'ministry_id' => $issuingUnit->ministry_id, // herite, jamais choisi
                'parent_id' => $issuingUnit->id,             // herite, jamais choisi
                'level_rank' => $attachmentCode->target_level_rank,
                'level_label' => $newUnitAttributes['level_label'],
                'name' => $newUnitAttributes['name'],
                'code' => $code,
                'metadata' => $newUnitAttributes['metadata'] ?? [],
                'status' => 'active',
                'path' => $issuingUnit->path.'.'.$code,
            ]);

            $newUnit->history()->create([
                'ministry_id' => $newUnit->ministry_id,
                'valid_from' => now()->toDateString(),
                'valid_to' => null,
                'name' => $newUnit->name,
                'level_rank' => $newUnit->level_rank,
                'level_label' => $newUnit->level_label,
                'parent_id' => $newUnit->parent_id,
                'path' => $newUnit->path,
                'transformation_type' => 'creation',
                'requested_by' => $usedBy->id,
                'approved_by' => $attachmentCode->issued_by,
                'reason' => 'Création via code de rattachement.',
            ]);

            // Sans cette affectation, la personne qui vient de rattacher
            // sa nouvelle entite n'a aucun droit dessus (OrgUnitPolicy::view
            // exige une affectation active sur le noeud ou un ancetre) et
            // se heurte a un refus d'acces immediatement apres avoir
            // "reussi" a la creer - le code Pasteur en fait le titulaire,
            // habilite (can_manage_users) a inviter d'autres personnes et
            // a emettre a son tour des codes pour ses propres rattachements.
            Affectation::create([
                'ministry_id' => $newUnit->ministry_id,
                'user_id' => $usedBy->id,
                'org_unit_id' => $newUnit->id,
                'role_id' => Role::where('code', Role::PASTEUR)->firstOrFail()->id,
                'status' => 'active',
                'started_at' => now()->toDateString(),
                'assigned_by' => $attachmentCode->issued_by,
            ]);

            $attachmentCode->update([
                'status' => 'utilise',
                'used_by' => $usedBy->id,
                'used_at' => now(),
                'created_org_unit_id' => $newUnit->id,
            ]);

            return [$newUnit, $usedBy];
        });
    }

    /**
     * Le code (slug court) n'est unique que dans le perimetre du parent
     * (contrainte ['parent_id', 'code'], voir migration org_units). Une
     * personne qui remplit ce formulaire ne connait pas forcement les
     * codes deja pris sous ce parent : on ajoute un suffixe numerique
     * plutot que de faire echouer la creation sur une contrainte SQL avec
     * un message illisible.
     */
    private function uniqueChildCode(OrgUnit $parent, string $desiredCode): string
    {
        $base = Str::slug($desiredCode) ?: 'entite';
        $code = $base;
        $suffix = 1;

        while (OrgUnit::where('parent_id', $parent->id)->where('code', $code)->exists()) {
            $suffix++;
            $code = "{$base}-{$suffix}";
        }

        return $code;
    }
}

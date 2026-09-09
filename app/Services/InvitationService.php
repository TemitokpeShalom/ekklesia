<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Invitation;
use App\Models\OrgUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Invitation individuelle a un poste precis (point 11) : jamais un compte
 * partage pour toute une eglise. Chaque personne garde un seul compte,
 * meme si elle cumule plusieurs affectations au fil du temps.
 */
class InvitationService
{
    public function invite(OrgUnit $orgUnit, Role $role, User $invitedBy, ?string $email = null, int $validForDays = 7): array
    {
        $plainToken = Str::random(40);

        $invitation = Invitation::create([
            'ministry_id' => $orgUnit->ministry_id,
            'org_unit_id' => $orgUnit->id,
            'role_id' => $role->id,
            'email' => $email,
            'token_hash' => Hash::make($plainToken),
            'status' => 'pending',
            'invited_by' => $invitedBy->id,
            'expires_at' => now()->addDays($validForDays),
        ]);

        return [$invitation, $plainToken];
    }

    /**
     * Retrouve l'invitation correspondant a un jeton en clair, quel que
     * soit son etat, et indique pourquoi elle n'est pas utilisable le cas
     * echeant. token_hash est hache avec un sel different a chaque fois
     * (Hash::make), donc impossible de retrouver la ligne par une requete
     * directe sur le hash : on doit comparer le jeton a chaque candidate.
     * Utilisee a la fois par acceptShow() (pour ne pas montrer un
     * formulaire voue a l'echec) et par accept() (pour le message exact).
     *
     * @return array{invitation: ?Invitation, reason: string}
     */
    public function resolve(string $plainToken): array
    {
        $candidate = Invitation::all()->first(
            fn (Invitation $candidate) => Hash::check($plainToken, $candidate->token_hash)
        );

        if (! $candidate) {
            return ['invitation' => null, 'reason' => 'introuvable'];
        }

        if ($candidate->status !== 'pending') {
            return ['invitation' => null, 'reason' => 'utilisee'];
        }

        if ($candidate->expires_at->isPast()) {
            return ['invitation' => null, 'reason' => 'expiree'];
        }

        return ['invitation' => $candidate, 'reason' => 'ok'];
    }

    public static function reasonMessage(string $reason): string
    {
        return match ($reason) {
            'utilisee' => 'Cette invitation a déjà été utilisée. Demandez-en une nouvelle à la personne qui vous a invité.',
            'expiree' => 'Cette invitation a expiré. Demandez-en une nouvelle à la personne qui vous a invité.',
            default => "Ce lien d'invitation est introuvable ou invalide. Vérifiez que vous avez copié l'adresse complète.",
        };
    }

    /**
     * Accepte une invitation. Si la personne n'a pas encore de compte, il
     * est cree ici ; si elle en a deja un (meme email), on lui ajoute
     * simplement une nouvelle affectation - jamais un second compte
     * (principe validé au point 11).
     */
    public function accept(string $plainToken, array $userAttributes): Affectation
    {
        ['invitation' => $invitation, 'reason' => $reason] = $this->resolve($plainToken);

        if (! $invitation) {
            throw new RuntimeException(self::reasonMessage($reason));
        }

        return DB::transaction(function () use ($invitation, $userAttributes) {
            // Cette requete precede forcement toute connexion (point 11) :
            // elle est hors du middleware tenant.context de routes/web.php,
            // donc app.current_ministry_id n'a jamais ete fixe pour elle.
            // Sans ce SET LOCAL, les policies RLS "fail closed" (point 04)
            // sur affectations/invitations rejetteraient silencieusement
            // les ecritures ci-dessous - meme mecanisme et meme raison que
            // AttachmentCodeService::consume().
            DB::statement("SET LOCAL app.current_ministry_id = '{$invitation->ministry_id}'");

            $user = User::where('email', $userAttributes['email'])->first();

            if (! $user) {
                $user = User::create([
                    'name' => $userAttributes['name'],
                    'email' => $userAttributes['email'],
                    'phone' => $userAttributes['phone'] ?? null,
                    'password' => $userAttributes['password'],
                ]);
            }

            $affectation = Affectation::create([
                'ministry_id' => $invitation->ministry_id,
                'user_id' => $user->id,
                'org_unit_id' => $invitation->org_unit_id,
                'role_id' => $invitation->role_id,
                'status' => 'active',
                'started_at' => now()->toDateString(),
                'assigned_by' => $invitation->invited_by,
            ]);

            $invitation->update([
                'status' => 'acceptee',
                'accepted_by' => $user->id,
                'accepted_at' => now(),
            ]);

            return $affectation;
        });
    }
}

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
     * Accepte une invitation. Si la personne n'a pas encore de compte, il
     * est cree ici ; si elle en a deja un (meme email), on lui ajoute
     * simplement une nouvelle affectation - jamais un second compte
     * (principe validé au point 11).
     */
    public function accept(string $plainToken, array $userAttributes): Affectation
    {
        $candidates = Invitation::where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get();

        $invitation = $candidates->first(
            fn (Invitation $candidate) => Hash::check($plainToken, $candidate->token_hash)
        );

        if (! $invitation) {
            throw new RuntimeException("Invitation invalide, déjà utilisée ou expirée.");
        }

        return DB::transaction(function () use ($invitation, $userAttributes) {
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

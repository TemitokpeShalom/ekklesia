<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Composition d'une equipe (point 08) : ajout et retrait d'un membre,
 * geres depuis l'ecran d'edition de l'equipe (Teams/Edit.vue), jamais par
 * un ecran dedie separe.
 */
class TeamMembersController extends Controller
{
    public function store(Request $request, OrgUnit $orgUnit, Team $equipe): RedirectResponse
    {
        $this->authorize('manageTeams', $orgUnit);
        abort_unless($equipe->org_unit_id === $orgUnit->id, 404);

        $data = $request->validate([
            'member_id' => [
                'required', 'uuid',
                Rule::exists('members', 'id')->where('org_unit_id', $orgUnit->id),
                Rule::unique('team_members')->where(fn ($q) => $q->where('team_id', $equipe->id)),
            ],
            'role_in_team' => ['nullable', 'string', 'max:255'],
            'joined_at' => ['nullable', 'date'],
        ], [
            'member_id.unique' => 'Ce membre fait déjà partie de cette équipe.',
        ]);

        $equipe->teamMembers()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
        ]);

        return redirect()->route('teams.edit', ['orgUnit' => $orgUnit->id, 'equipe' => $equipe->id]);
    }

    public function destroy(OrgUnit $orgUnit, Team $equipe, TeamMember $membre): RedirectResponse
    {
        $this->authorize('manageTeams', $orgUnit);
        abort_unless($equipe->org_unit_id === $orgUnit->id, 404);
        abort_unless($membre->team_id === $equipe->id, 404);

        $membre->delete();

        return redirect()->route('teams.edit', ['orgUnit' => $orgUnit->id, 'equipe' => $equipe->id]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\Team;
use App\Models\TeamMember;
use App\Services\DiscipleshipStageRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Composition d'une equipe (point 08) : ajout et retrait d'un membre.
 * Corrige le 2026-09-12 (retour du ministere) : deplace sur son propre
 * ecran (Teams/Membres.vue), ouvert en cliquant directement sur l'equipe
 * depuis la liste - plus depuis l'ecran "Modifier" de l'equipe, desormais
 * reserve au nom/a la description (voir TeamsController).
 */
class TeamMembersController extends Controller
{
    public function index(OrgUnit $orgUnit, Team $equipe): Response
    {
        $this->authorize('manageTeams', $orgUnit);
        abort_unless($equipe->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Teams/Membres', [
            'orgUnit' => $orgUnit,
            'equipe' => $equipe->load('teamMembers.member'),
            'members' => $orgUnit->members()->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    /**
     * Corrige le 2026-09-12 (retour du ministere) : "si elle n'est pas dans
     * la base, qu'on puisse enregistrer directement et faire l'automatisation
     * pour que ça crée en même temps dans les membres" - meme mecanisme que
     * Parcours de disciple (Member::createMinimal) : un nom saisi sans
     * selection dans la liste cree une fiche membre minimale, completable
     * ensuite depuis le module Membres.
     *
     * Egalement : "dès que quelqu'un est enregistré dans n'importe quelle
     * équipe ici... c'est déjà une personne qui est engagée dans le
     * service" - contrairement aux Sacrements/bapteme (une seule etape
     * bien precise), ici Martin a explicitement demande une regle
     * universelle, peu importe l'equipe : pas de configuration par equipe a
     * gerer, une simple ligne suffit.
     */
    public function store(Request $request, OrgUnit $orgUnit, Team $equipe): RedirectResponse
    {
        $this->authorize('manageTeams', $orgUnit);
        abort_unless($equipe->org_unit_id === $orgUnit->id, 404);

        $data = $request->validate([
            'member_id' => [
                'nullable', 'uuid',
                Rule::exists('members', 'id')->where('org_unit_id', $orgUnit->id),
                Rule::unique('team_members')->where(fn ($q) => $q->where('team_id', $equipe->id)),
            ],
            'new_member_name' => ['nullable', 'string', 'max:255'],
            'role_in_team' => ['nullable', 'string', 'max:255'],
            'joined_at' => ['nullable', 'date'],
        ], [
            'member_id.unique' => 'Ce membre fait déjà partie de cette équipe.',
        ]);

        abort_if(
            empty($data['member_id']) && empty($data['new_member_name']),
            422,
            'Indiquez le membre concerné, ou à défaut son nom.'
        );

        if (empty($data['member_id'])) {
            $data['member_id'] = Member::createMinimal($orgUnit, $data['new_member_name'])->id;
        }

        unset($data['new_member_name']);

        $teamMember = $equipe->teamMembers()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
        ]);

        DiscipleshipStageRecorder::record(
            $teamMember->member,
            'engage_service',
            $teamMember->joined_at?->toDateString()
        );

        // Corrige le 2026-09-12 (retour du ministere, valable pour TOUS les
        // modules d'enregistrement) : confirmer clairement la reussite,
        // toujours en vert - jamais en rouge, reserve aux erreurs.
        return redirect()->route('team-members.index', ['orgUnit' => $orgUnit->id, 'equipe' => $equipe->id])
            ->with('success', 'Membre ajouté à l\'équipe.');
    }

    public function destroy(OrgUnit $orgUnit, Team $equipe, TeamMember $membre): RedirectResponse
    {
        $this->authorize('manageTeams', $orgUnit);
        abort_unless($equipe->org_unit_id === $orgUnit->id, 404);
        abort_unless($membre->team_id === $equipe->id, 404);

        $membre->delete();

        return redirect()->route('team-members.index', ['orgUnit' => $orgUnit->id, 'equipe' => $equipe->id])
            ->with('success', 'Membre retiré de l\'équipe.');
    }
}

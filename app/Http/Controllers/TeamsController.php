<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Equipes de service et benevolat (point 08). La composition de chaque
 * equipe (ajout/retrait d'un membre) est geree par TeamMembersController,
 * directement depuis l'ecran d'edition d'une equipe.
 */
class TeamsController extends Controller
{
    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $teams = $orgUnit->teams()
            ->withCount('teamMembers')
            ->orderBy('name')
            ->get();

        return Inertia::render('Teams/Index', [
            'orgUnit' => $orgUnit,
            'teams' => $teams,
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageTeams', $orgUnit);

        return Inertia::render('Teams/Create', [
            'orgUnit' => $orgUnit,
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageTeams', $orgUnit);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $orgUnit->teams()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
        ]);

        return redirect()->route('teams.index', ['orgUnit' => $orgUnit->id]);
    }

    public function edit(OrgUnit $orgUnit, Team $equipe): Response
    {
        $this->authorize('manageTeams', $orgUnit);
        abort_unless($equipe->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Teams/Edit', [
            'orgUnit' => $orgUnit,
            'equipe' => $equipe->load('teamMembers.member'),
            'members' => $orgUnit->members()->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, Team $equipe): RedirectResponse
    {
        $this->authorize('manageTeams', $orgUnit);
        abort_unless($equipe->org_unit_id === $orgUnit->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $equipe->update($data);

        return redirect()->route('teams.index', ['orgUnit' => $orgUnit->id]);
    }

    public function destroy(OrgUnit $orgUnit, Team $equipe): RedirectResponse
    {
        $this->authorize('manageTeams', $orgUnit);
        abort_unless($equipe->org_unit_id === $orgUnit->id, 404);

        $equipe->delete();

        return redirect()->route('teams.index', ['orgUnit' => $orgUnit->id]);
    }
}

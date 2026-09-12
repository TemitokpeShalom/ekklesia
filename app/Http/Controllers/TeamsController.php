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
 * sur son propre ecran (Teams/Membres.vue) - separe de celui-ci, qui ne
 * gere plus que l'identite de l'equipe (nom, description).
 *
 * Corrige le 2026-09-12 (retour du ministere) : jusqu'ici, cliquer sur une
 * equipe dans la liste ne faisait rien ("il faut cliquer sur modifier pour
 * que ça puisse réagir") et "modifier" combinait a tort deux choses tres
 * differentes (renommer l'equipe / gerer ses membres) sur un seul ecran.
 * Desormais : cliquer sur une equipe dans la liste ouvre directement la
 * gestion de ses membres (l'action la plus frequente) ; "Modifier" reste un
 * lien separe, reserve au nom/a la description de l'equipe.
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

        // Corrige le 2026-09-12 (retour du ministere, valable pour TOUS les
        // modules d'enregistrement) : confirmer clairement la reussite,
        // toujours en vert - jamais en rouge, reserve aux erreurs.
        return redirect()->route('teams.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Équipe créée.');
    }

    public function edit(OrgUnit $orgUnit, Team $equipe): Response
    {
        $this->authorize('manageTeams', $orgUnit);
        abort_unless($equipe->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Teams/Edit', [
            'orgUnit' => $orgUnit,
            'equipe' => $equipe,
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

        return redirect()->route('teams.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Équipe mise à jour.');
    }

    public function destroy(OrgUnit $orgUnit, Team $equipe): RedirectResponse
    {
        $this->authorize('manageTeams', $orgUnit);
        abort_unless($equipe->org_unit_id === $orgUnit->id, 404);

        $equipe->delete();

        return redirect()->route('teams.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Équipe supprimée.');
    }
}

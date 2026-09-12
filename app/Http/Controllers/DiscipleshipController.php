<?php

namespace App\Http\Controllers;

use App\Models\DiscipleshipStage;
use App\Models\Member;
use App\Models\OrgUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Parcours de disciple (point 08) : suivi des etapes de croissance
 * spirituelle par membre. Chaque enregistrement est une etape franchie a
 * une date donnee (journal append-only, jamais une case a cocher que l'on
 * ecrase) - l'ecran affiche l'etape la plus recente de chaque membre,
 * calculee directement depuis ce journal.
 */
class DiscipleshipController extends Controller
{
    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $members = $orgUnit->members()
            ->with('latestDiscipleshipStage')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Discipleship/Index', [
            'orgUnit' => $orgUnit,
            'members' => $members,
            'stages' => DiscipleshipStage::STAGES,
        ]);
    }

    public function create(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('manageDiscipleship', $orgUnit);

        return Inertia::render('Discipleship/Create', [
            'orgUnit' => $orgUnit,
            'members' => $orgUnit->members()->orderBy('last_name')->orderBy('first_name')->get(),
            'stages' => DiscipleshipStage::STAGES,
            'preselectedMemberId' => $request->query('membre'),
        ]);
    }

    /**
     * Corrige le 2026-09-12 (retour du ministere) : "si la personne n'était
     * pas dans la base, qu'on puisse renseigner son nom" - meme besoin que
     * pour les Sacrements, mais la mecanique differe car cet ecran (et le
     * tableau Parcours de disciple) est organise PAR MEMBRE : une simple
     * etiquette de nom libre, sans fiche membre reelle, resterait invisible
     * partout ailleurs dans l'application (recherche, sacrements, equipes...).
     * On cree donc ici une fiche membre minimale (nom seul, le reste
     * completable plus tard depuis le module Membres) plutot qu'un nom
     * libre "orphelin" - la personne devient un vrai membre suivi partout,
     * ce qui correspond mieux a l'esprit de la demande.
     */
    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageDiscipleship', $orgUnit);

        $data = $request->validate([
            'member_id' => ['nullable', 'uuid', 'exists:members,id'],
            'new_member_name' => ['nullable', 'string', 'max:255'],
            'stage' => ['required', 'string', 'in:'.implode(',', array_keys(DiscipleshipStage::STAGES))],
            'reached_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
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

        $orgUnit->discipleshipStages()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
        ]);

        // Corrige le 2026-09-12 (retour du ministere, valable pour TOUS les
        // modules d'enregistrement) : confirmer clairement la reussite,
        // toujours en vert - jamais en rouge, reserve aux erreurs.
        return redirect()->route('discipleship.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Étape enregistrée.');
    }

    public function edit(OrgUnit $orgUnit, DiscipleshipStage $etape): Response
    {
        $this->authorize('manageDiscipleship', $orgUnit);
        abort_unless($etape->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Discipleship/Edit', [
            'orgUnit' => $orgUnit,
            'etape' => $etape,
            'members' => $orgUnit->members()->orderBy('last_name')->orderBy('first_name')->get(),
            'stages' => DiscipleshipStage::STAGES,
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, DiscipleshipStage $etape): RedirectResponse
    {
        $this->authorize('manageDiscipleship', $orgUnit);
        abort_unless($etape->org_unit_id === $orgUnit->id, 404);

        $data = $request->validate([
            'member_id' => ['required', 'uuid', 'exists:members,id'],
            'stage' => ['required', 'string', 'in:'.implode(',', array_keys(DiscipleshipStage::STAGES))],
            'reached_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $etape->update($data);

        return redirect()->route('discipleship.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Étape mise à jour.');
    }

    public function destroy(OrgUnit $orgUnit, DiscipleshipStage $etape): RedirectResponse
    {
        $this->authorize('manageDiscipleship', $orgUnit);
        abort_unless($etape->org_unit_id === $orgUnit->id, 404);

        $etape->delete();

        return redirect()->route('discipleship.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Étape retirée.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DiscipleshipStage;
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

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageDiscipleship', $orgUnit);

        $data = $request->validate([
            'member_id' => ['required', 'uuid', 'exists:members,id'],
            'stage' => ['required', 'string', 'in:'.implode(',', array_keys(DiscipleshipStage::STAGES))],
            'reached_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $orgUnit->discipleshipStages()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
        ]);

        return redirect()->route('discipleship.index', ['orgUnit' => $orgUnit->id]);
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

        return redirect()->route('discipleship.index', ['orgUnit' => $orgUnit->id]);
    }

    public function destroy(OrgUnit $orgUnit, DiscipleshipStage $etape): RedirectResponse
    {
        $this->authorize('manageDiscipleship', $orgUnit);
        abort_unless($etape->org_unit_id === $orgUnit->id, 404);

        $etape->delete();

        return redirect()->route('discipleship.index', ['orgUnit' => $orgUnit->id]);
    }
}

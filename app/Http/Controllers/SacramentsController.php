<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Sacrament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SacramentsController extends Controller
{
    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $sacraments = $orgUnit->sacraments()
            ->with(['member', 'spouseMember'])
            ->orderByDesc('event_date')
            ->get();

        return Inertia::render('Sacraments/Index', [
            'orgUnit' => $orgUnit,
            'sacraments' => $sacraments,
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageSacrements', $orgUnit);

        return Inertia::render('Sacraments/Create', [
            'orgUnit' => $orgUnit,
            'members' => $orgUnit->members()->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageSacrements', $orgUnit);

        $data = $request->validate([
            'type' => ['required', 'string', 'in:bapteme,mariage'],
            'member_id' => ['required', 'uuid', 'exists:members,id'],
            'spouse_member_id' => ['nullable', 'uuid', 'exists:members,id'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'officiant' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $orgUnit->sacraments()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
        ]);

        return redirect()->route('sacrements.index', ['orgUnit' => $orgUnit->id]);
    }

    public function edit(OrgUnit $orgUnit, Sacrament $sacrement): Response
    {
        $this->authorize('manageSacrements', $orgUnit);
        abort_unless($sacrement->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Sacraments/Edit', [
            'orgUnit' => $orgUnit,
            'sacrement' => $sacrement->load(['member', 'spouseMember']),
            'members' => $orgUnit->members()->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, Sacrament $sacrement): RedirectResponse
    {
        $this->authorize('manageSacrements', $orgUnit);
        abort_unless($sacrement->org_unit_id === $orgUnit->id, 404);

        $data = $request->validate([
            'type' => ['required', 'string', 'in:bapteme,mariage'],
            'member_id' => ['required', 'uuid', 'exists:members,id'],
            'spouse_member_id' => ['nullable', 'uuid', 'exists:members,id'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'officiant' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $sacrement->update($data);

        return redirect()->route('sacrements.index', ['orgUnit' => $orgUnit->id]);
    }

    public function destroy(OrgUnit $orgUnit, Sacrament $sacrement): RedirectResponse
    {
        $this->authorize('manageSacrements', $orgUnit);
        abort_unless($sacrement->org_unit_id === $orgUnit->id, 404);

        $sacrement->delete();

        return redirect()->route('sacrements.index', ['orgUnit' => $orgUnit->id]);
    }
}

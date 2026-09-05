<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\OrgUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des membres (fideles) rattaches a une unite d'organisation.
 * Isolation stricte par ministry_id (point 04) : chaque membre appartient
 * au meme ministere que l'unite d'organisation a laquelle il est rattache.
 */
class MembersController extends Controller
{
    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        return Inertia::render('Members/Index', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'members' => $orgUnit->members()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'phone', 'email', 'status', 'joined_at']),
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageMembers', $orgUnit);

        return Inertia::render('Members/Create', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', 'string', 'in:M,F'],
            'birth_date' => ['nullable', 'date'],
            'joined_at' => ['nullable', 'date'],
        ]);

        $orgUnit->members()->create([
            ...$data,
            'ministry_id' => $orgUnit->ministry_id,
            'status' => 'active',
        ]);

        return redirect()->route('members.index', ['orgUnit' => $orgUnit->id]);
    }

    public function edit(OrgUnit $orgUnit, Member $member): Response
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($member->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Members/Edit', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'member' => $member,
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, Member $member): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($member->org_unit_id === $orgUnit->id, 404);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', 'string', 'in:M,F'],
            'birth_date' => ['nullable', 'date'],
            'joined_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $member->update($data);

        return redirect()->route('members.index', ['orgUnit' => $orgUnit->id]);
    }

    public function destroy(OrgUnit $orgUnit, Member $member): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($member->org_unit_id === $orgUnit->id, 404);

        $member->delete();

        return redirect()->route('members.index', ['orgUnit' => $orgUnit->id]);
    }
}

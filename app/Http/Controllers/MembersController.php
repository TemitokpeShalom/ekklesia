<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\OrgUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des membres (fideles) rattaches a une unite d'organisation.
 * Isolation stricte par ministry_id (point 04) : chaque membre appartient
 * au meme ministere que l'unite d'organisation a laquelle il est rattache.
 */
class MembersController extends Controller
{
    // Photos de profil (+ conjoint), point 08 : meme stockage local (disque
    // "public") que les pieces jointes des annonces (point 07).
    private const MAX_PHOTO_KB = 5120;

    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        return Inertia::render('Members/Index', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'members' => $orgUnit->members()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'phone', 'email', 'status', 'joined_at', 'photo_path']),
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageMembers', $orgUnit);

        return Inertia::render('Members/Create', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'honorificTitles' => $orgUnit->ministry->honorificTitles(),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);

        $data = $this->validateMember($request);
        $data['photo_path'] = $this->storePhoto($request, 'photo');
        $data['spouse_photo_path'] = $this->storePhoto($request, 'spouse_photo');

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
            'honorificTitles' => $orgUnit->ministry->honorificTitles(),
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, Member $member): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($member->org_unit_id === $orgUnit->id, 404);

        $data = $this->validateMember($request);
        $data['status'] = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive'],
        ])['status'];

        if ($request->boolean('remove_photo') && $member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
            $data['photo_path'] = null;
        } elseif ($request->hasFile('photo')) {
            if ($member->photo_path) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $data['photo_path'] = $this->storePhoto($request, 'photo');
        }

        if ($request->boolean('remove_spouse_photo') && $member->spouse_photo_path) {
            Storage::disk('public')->delete($member->spouse_photo_path);
            $data['spouse_photo_path'] = null;
        } elseif ($request->hasFile('spouse_photo')) {
            if ($member->spouse_photo_path) {
                Storage::disk('public')->delete($member->spouse_photo_path);
            }
            $data['spouse_photo_path'] = $this->storePhoto($request, 'spouse_photo');
        }

        $member->update($data);

        return redirect()->route('members.index', ['orgUnit' => $orgUnit->id]);
    }

    public function destroy(OrgUnit $orgUnit, Member $member): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($member->org_unit_id === $orgUnit->id, 404);

        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }
        if ($member->spouse_photo_path) {
            Storage::disk('public')->delete($member->spouse_photo_path);
        }

        $member->delete();

        return redirect()->route('members.index', ['orgUnit' => $orgUnit->id]);
    }

    private function validateMember(Request $request): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', 'string', 'in:M,F'],
            'birth_date' => ['nullable', 'date'],
            'joined_at' => ['nullable', 'date'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:'.self::MAX_PHOTO_KB],
            'spouse_photo' => ['nullable', 'image', 'max:'.self::MAX_PHOTO_KB],
        ]);

        unset($data['photo'], $data['spouse_photo']);

        return $data;
    }

    private function storePhoto(Request $request, string $field): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return $request->file($field)->store('membres/photos', 'public');
    }
}

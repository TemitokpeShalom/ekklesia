<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Role;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations)
    {
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('inviteTo', $orgUnit);

        return Inertia::render('OrgUnits/Invite', [
            'orgUnit' => $orgUnit,
            'roles' => Role::orderBy('label')->get(['id', 'code', 'label']),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('inviteTo', $orgUnit);

        $validated = $request->validate([
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
            'email' => ['nullable', 'email'],
        ]);

        [$invitation, $plainToken] = $this->invitations->invite(
            $orgUnit,
            Role::findOrFail($validated['role_id']),
            $request->user(),
            $validated['email'] ?? null,
        );

        // Le lien complet (avec le jeton en clair) est envoye par
        // notification (email/sms) - hors perimetre de ce premier module ;
        // affiche ici pour permettre un partage manuel en attendant.
        return back()->with('invitation_link', route('invitations.accept.show', ['token' => $plainToken]));
    }

    public function acceptShow(string $token): Response
    {
        return Inertia::render('Invitations/Accept', ['token' => $token]);
    }

    public function acceptStore(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $affectation = $this->invitations->accept($token, [
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        auth()->login($affectation->user);

        return redirect()->route('dashboard', ['orgUnit' => $affectation->org_unit_id]);
    }
}

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
        // Verifie le jeton avant d'afficher le formulaire : inutile de
        // laisser quelqu'un remplir nom/e-mail/mot de passe pour se
        // heurter ensuite a une invitation deja utilisee ou expiree.
        ['invitation' => $invitation, 'reason' => $reason] = $this->invitations->resolve($token);

        return Inertia::render('Invitations/Accept', [
            'token' => $token,
            'valid' => $invitation !== null,
            'error' => $invitation === null ? InvitationService::reasonMessage($reason) : null,
        ]);
    }

    public function acceptStore(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $affectation = $this->invitations->accept($token, [
                ...$validated,
                'password' => Hash::make($validated['password']),
            ]);
        } catch (\RuntimeException $e) {
            // Rejoue possible entre l'affichage et la soumission (lien
            // ouvert dans deux onglets, double clic) : on renvoie un
            // message clair au lieu de laisser l'exception remonter en
            // page d'erreur 500 brute.
            return back()->withErrors(['invitation' => $e->getMessage()]);
        }

        auth()->login($affectation->user);

        return redirect()->route('dashboard', ['orgUnit' => $affectation->org_unit_id]);
    }
}

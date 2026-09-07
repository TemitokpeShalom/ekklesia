<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\OrgUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Continuite des acces (point 16) : lister les titulaires actifs d'un
 * noeud et permettre a un niveau habilite de revoquer une affectation
 * (deces, perte d'acces, remplacement...). Une affectation revoquee
 * n'est jamais supprimee, seulement marquee (meme regle que partout
 * ailleurs dans l'application : rien n'est perdu, tout est historise).
 * Meme droit que l'invitation (inviteTo) : un role habilite a gerer des
 * personnes, sur ce noeud ou un ancetre.
 */
class AffectationsController extends Controller
{
    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('inviteTo', $orgUnit);

        $affectations = Affectation::where('org_unit_id', $orgUnit->id)
            ->where('status', 'active')
            ->with(['user:id,name,email,phone', 'role:id,label'])
            ->orderBy('started_at')
            ->get(['id', 'user_id', 'role_id', 'org_unit_id', 'started_at']);

        return Inertia::render('OrgUnits/Affectations', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'affectations' => $affectations,
        ]);
    }

    public function destroy(Request $request, OrgUnit $orgUnit, Affectation $affectation): RedirectResponse
    {
        $this->authorize('inviteTo', $orgUnit);

        abort_unless($affectation->org_unit_id === $orgUnit->id, 404);

        $validated = $request->validate([
            'revocation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $affectation->update([
            'status' => 'revoquee',
            'ended_at' => now()->toDateString(),
            'revoked_by' => $request->user()->id,
            'revocation_reason' => $validated['revocation_reason'] ?? null,
        ]);

        return back()->with('success', "Affectation révoquée. Vous pouvez maintenant inviter un remplaçant si besoin.");
    }
}

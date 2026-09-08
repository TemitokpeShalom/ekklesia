<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Abonnement et facturation (point 15). Aucune passerelle de paiement
 * n'est branchee ici : changer d'offre met a jour l'etat de l'abonnement
 * immediatement (periode d'un mois), comme le ferait un administrateur
 * traitant un paiement recu hors ligne (virement, especes - frequent pour
 * ce type d'organisation). Brancher un vrai prestataire (Stripe, CinetPay,
 * etc.) remplacera cette mise a jour directe sans toucher a l'ecran cote
 * utilisateur. Reserve a la racine de l'arbre (rang 0), meme droit que la
 * gouvernance des acces.
 */
class SubscriptionController extends Controller
{
    public function edit(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($orgUnit->level_rank === OrgUnit::RANK_MINISTERE, 404);

        $ministry = $orgUnit->ministry;

        return Inertia::render('Settings/Abonnement', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'plans' => Plan::orderBy('sort_order')->get(['id', 'code', 'name', 'price_monthly', 'currency', 'max_members', 'features']),
            'subscription' => [
                'plan_id' => $ministry->plan_id,
                'status' => $ministry->subscription_status,
                'on_trial' => $ministry->onTrial(),
                'trial_ends_at' => optional($ministry->trial_ends_at)->toIso8601String(),
                'current_period_ends_at' => optional($ministry->current_period_ends_at)->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($orgUnit->level_rank === OrgUnit::RANK_MINISTERE, 404);

        $data = $request->validate([
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
        ]);

        $orgUnit->ministry->update([
            'plan_id' => $data['plan_id'],
            'subscription_status' => 'active',
            'current_period_ends_at' => now()->addMonth(),
        ]);

        return redirect()->route('subscription.edit', ['orgUnit' => $orgUnit->id]);
    }
}

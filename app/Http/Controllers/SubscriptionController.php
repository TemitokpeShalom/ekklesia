<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Abonnement et facturation (point 15). update() reste la mise a jour
 * directe d'origine (aucune preuve de paiement exigee) : elle sert
 * desormais au traitement d'un paiement recu hors ligne (virement, especes
 * - frequent pour ce type d'organisation), exactement comme avant. Les deux
 * moyens de paiement en ligne (FedaPay pour l'Afrique de l'Ouest/Centrale,
 * crypto pour la diaspora) sont branches separement - voir
 * SubscriptionFedapayController et SubscriptionCryptoController - et
 * mettent a jour le meme etat sur Ministry une fois le paiement verifie.
 * Reserve a la racine de l'arbre (rang 0), meme droit que la gouvernance
 * des acces.
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
            // Point 15 : chaque moyen de paiement ne s'affiche que s'il est
            // reellement configure sur ce serveur (voir .env) - jamais un
            // bouton qui echouerait faute de cles.
            'payment' => [
                'fedapay_available' => filled(config('fedapay.public_key')) && filled(config('fedapay.secret_key')),
                'crypto_wallet_address' => config('crypto.wallet_address'),
            ],
            'paymentHistory' => $ministry->subscriptionPayments()
                ->with('plan:id,name')
                ->latest()
                ->limit(10)
                ->get(['id', 'plan_id', 'provider', 'status', 'amount', 'currency', 'created_at']),
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

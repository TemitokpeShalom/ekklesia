<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\CryptoPaymentVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Point 15 (passerelle de paiement, diaspora) : reglement direct
 * portefeuille-a-portefeuille (USDT ou BNB, BNB Smart Chain), verifie par
 * hash on-chain (voir CryptoPaymentVerifier) plutot qu'accepte a l'aveugle.
 * Aucune conversion de change n'existant dans l'application (meme regle
 * que les Finances, point 18), le montant exact du plan n'est pas
 * confronte a la somme recue : c'est l'administrateur, qui vient lui-meme
 * d'effectuer le paiement, qui choisit quel abonnement ce paiement couvre -
 * la verification on-chain garantit seulement que le paiement est reel,
 * confirme, et envoye a l'adresse du ministere.
 */
class SubscriptionCryptoController extends Controller
{
    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($orgUnit->level_rank === OrgUnit::RANK_MINISTERE, 404);

        $data = $request->validate([
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
            'tx_hash' => ['required', 'regex:/^0x[a-fA-F0-9]{64}$/'],
        ]);

        if (SubscriptionPayment::where('provider', SubscriptionPayment::PROVIDER_CRYPTO)
            ->where('provider_reference', $data['tx_hash'])
            ->exists()) {
            throw ValidationException::withMessages([
                'tx_hash' => 'Cette transaction a déjà été utilisée pour activer un abonnement.',
            ]);
        }

        $result = (new CryptoPaymentVerifier)->verify($data['tx_hash']);

        if (! $result['confirmed']) {
            return back()->with('error', $this->explainRejection($result['reason']));
        }

        $plan = Plan::findOrFail($data['plan_id']);
        $ministry = $orgUnit->ministry;

        $payment = SubscriptionPayment::create([
            'ministry_id' => $ministry->id,
            'plan_id' => $plan->id,
            'provider' => SubscriptionPayment::PROVIDER_CRYPTO,
            'status' => SubscriptionPayment::STATUS_SUCCESS,
            'amount' => $result['amount'],
            'currency' => $result['token'],
            'provider_reference' => $data['tx_hash'],
            'metadata' => ['to' => $result['to']],
        ]);

        $ministry->update([
            'plan_id' => $plan->id,
            'subscription_status' => 'active',
            'current_period_ends_at' => now()->addMonth(),
        ]);

        return redirect()->route('subscription.edit', ['orgUnit' => $orgUnit->id])
            ->with('success', "Paiement {$result['token']} vérifié ({$payment->amount} {$result['token']}) : abonnement « {$plan->name} » activé.");
    }

    private function explainRejection(?string $reason): string
    {
        return match ($reason) {
            'introuvable_ou_en_attente' => "Transaction introuvable sur la chaîne, ou pas encore confirmée : réessayez dans quelques instants.",
            'echouee_on_chain' => 'Cette transaction a échoué sur la chaîne : aucun fonds ne semble avoir été transféré.',
            'destinataire_incorrect' => "Cette transaction n'a pas été envoyée à l'adresse du ministère.",
            'transfert_usdt_illisible' => "Le transfert USDT n'a pas pu être lu dans cette transaction.",
            default => 'Cette transaction ne peut pas être vérifiée pour le moment.',
        };
    }
}

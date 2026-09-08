<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\CryptoPaymentVerifier;
use App\Services\ExchangeRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Point 15 (passerelle de paiement, diaspora) : reglement direct
 * portefeuille-a-portefeuille (USDT ou BNB, BNB Smart Chain), verifie par
 * hash on-chain (voir CryptoPaymentVerifier) plutot qu'accepte a l'aveugle.
 *
 * Depuis le 08/09/2026, le montant recu est aussi confronte au prix du plan
 * (converti en USD via ExchangeRateService, le franc CFA n'ayant pas de
 * cours flottant propre - voir cette classe) : avant cette evolution,
 * n'importe quel montant confirme et envoye a la bonne adresse etait
 * accepte, l'administrateur choisissant lui-meme quel abonnement le
 * paiement couvrait. Cette confrontation utilise une marge de tolerance
 * (voir CryptoPaymentVerifier::MARGE_USDT / MARGE_BNB) : le cours au moment
 * de l'affichage du prix et celui au moment de l'envoi effectif du paiement
 * peuvent legerement diverger.
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

        $plan = Plan::findOrFail($data['plan_id']);

        try {
            // Seul XOF est utilise pour les plans a ce jour (voir
            // PlanSeeder) : la conversion ne sait faire que XOF -> USD.
            $expectedUsd = (new ExchangeRateService)->xofToUsd((float) $plan->price_monthly);
        } catch (RuntimeException $e) {
            return back()->with('error', 'Impossible de vérifier le montant pour le moment (cours de change indisponible) : réessayez dans quelques instants.');
        }

        $result = (new CryptoPaymentVerifier)->verify($data['tx_hash'], $expectedUsd);

        if (! $result['confirmed']) {
            return back()->with('error', $this->explainRejection($result['reason']));
        }

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
            'montant_insuffisant' => "Le montant reçu est insuffisant par rapport au prix du plan au cours actuel. Envoyez le complément, ou renvoyez le montant exact affiché sur la page.",
            'cours_indisponible' => 'Impossible de vérifier le montant pour le moment (cours de change indisponible) : réessayez dans quelques instants.',
            default => 'Cette transaction ne peut pas être vérifiée pour le moment.',
        };
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Services\FedaPayClient;
use App\Services\FedaPaySignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Point 15 (passerelle de paiement, Afrique de l'Ouest/Centrale, FedaPay).
 * Deux temps bien separes : checkout() est appele par un administrateur
 * connecte (contexte tenant deja en place) et redirige vers la page de
 * paiement hebergee par FedaPay ; webhook() est appele par les serveurs de
 * FedaPay eux-memes, jamais authentifie ni dans le contexte tenant normal -
 * c'est la signature (FedaPaySignatureVerifier) qui en tient lieu. Ni
 * subscription_payments ni ministries ne portent de RLS (voir le
 * commentaire de la migration create_subscription_payments_table), donc
 * aucun contexte a fixer a la main ici : la seule protection necessaire
 * est deja la signature verifiee plus haut.
 */
class SubscriptionFedapayController extends Controller
{
    /**
     * Renvoie une reponse Inertia::location() (jamais un redirect() classique)
     * : la requete arrive en XHR avec les en-tetes Inertia (bouton "Payer"
     * sur la page Abonnement), et seul Inertia::location() sait declencher
     * une vraie navigation plein-ecran du navigateur vers un domaine externe
     * (window.location, pas un rechargement XHR de la page).
     */
    public function checkout(Request $request, OrgUnit $orgUnit): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($orgUnit->level_rank === OrgUnit::RANK_MINISTERE, 404);

        $data = $request->validate([
            'plan_id' => ['required', 'uuid', 'exists:plans,id'],
        ]);

        $plan = Plan::findOrFail($data['plan_id']);
        $ministry = $orgUnit->ministry;

        $payment = SubscriptionPayment::create([
            'ministry_id' => $ministry->id,
            'plan_id' => $plan->id,
            'provider' => SubscriptionPayment::PROVIDER_FEDAPAY,
            'status' => SubscriptionPayment::STATUS_PENDING,
            'amount' => $plan->price_monthly,
            'currency' => $plan->currency,
        ]);

        try {
            $client = new FedaPayClient;

            $transaction = $client->createTransaction([
                'description' => "Abonnement Ekklesia - {$plan->name} - {$ministry->name}",
                'amount' => (int) round($plan->price_monthly),
                'currency' => ['iso' => $plan->currency],
                'callback_url' => route('subscription.edit', ['orgUnit' => $orgUnit->id]),
                'custom_metadata' => ['subscription_payment_id' => $payment->id],
            ]);

            $payment->update(['provider_reference' => (string) $transaction['id']]);

            $token = $client->generateToken($transaction['id']);
        } catch (RuntimeException $e) {
            Log::error('FedaPay: echec du demarrage du paiement.', ['payment_id' => $payment->id, 'message' => $e->getMessage()]);
            $payment->update(['status' => SubscriptionPayment::STATUS_FAILED]);

            // Le motif precis (ex. plafond de transaction du compte FedaPay
            // depasse) vient directement de FedaPayClient - utile a
            // l'administrateur qui configure le paiement, jamais montre a
            // un visiteur non authentifie puisque cette page exige deja
            // manageMembers.
            return redirect()->route('subscription.edit', ['orgUnit' => $orgUnit->id])
                ->with('error', "Le paiement FedaPay n'a pas pu démarrer : {$e->getMessage()}");
        }

        return Inertia::location($token['url']);
    }

    /**
     * Point de terminaison public (voir routes/web.php, hors groupe
     * auth/tenant.context, et exclu de la verification CSRF dans
     * bootstrap/app.php - FedaPay ne peut pas fournir de jeton CSRF).
     */
    public function webhook(Request $request): Response
    {
        $rawBody = $request->getContent();

        try {
            FedaPaySignatureVerifier::verify(
                $rawBody,
                $request->header('X-FEDAPAY-SIGNATURE'),
                (string) config('fedapay.webhook_secret'),
            );
        } catch (RuntimeException $e) {
            Log::warning('FedaPay webhook: signature rejetee.', ['message' => $e->getMessage()]);

            return response('signature invalide', 400);
        }

        $payload = json_decode($rawBody, true) ?? [];
        Log::info('FedaPay webhook: evenement recu.', ['payload' => $payload]);

        $type = $payload['type'] ?? $payload['name'] ?? null;
        $objectId = $payload['object_id'] ?? $payload['object']['id'] ?? null;

        if (! is_string($type) || ! str_starts_with($type, 'transaction.') || ! $objectId) {
            // Evenement non lie a une transaction (ex. customer.created) :
            // rien a faire, on accuse simplement reception.
            return response()->json(['received' => true]);
        }

        try {
            $transaction = (new FedaPayClient)->retrieveTransaction($objectId);
        } catch (RuntimeException $e) {
            Log::error('FedaPay webhook: relecture de la transaction impossible.', ['object_id' => $objectId, 'message' => $e->getMessage()]);

            return response()->json(['received' => true]);
        }

        $paymentId = $transaction['custom_metadata']['subscription_payment_id'] ?? null;

        $payment = $paymentId
            ? SubscriptionPayment::find($paymentId)
            : SubscriptionPayment::where('provider', SubscriptionPayment::PROVIDER_FEDAPAY)
                ->where('provider_reference', (string) $objectId)
                ->first();

        if (! $payment) {
            Log::warning('FedaPay webhook: aucun paiement local correspondant.', ['object_id' => $objectId, 'metadata' => $transaction['custom_metadata']]);

            return response()->json(['received' => true]);
        }

        if ($payment->status !== SubscriptionPayment::STATUS_PENDING) {
            // Deja traite (webhook rejoue) : on accuse reception sans rien refaire.
            return response()->json(['received' => true]);
        }

        if ($transaction['status'] === 'approved') {
            $payment->update([
                'status' => SubscriptionPayment::STATUS_SUCCESS,
                'metadata' => ['fedapay_status' => $transaction['status']],
            ]);

            if ($payment->plan_id) {
                $payment->ministry->update([
                    'plan_id' => $payment->plan_id,
                    'subscription_status' => 'active',
                    'current_period_ends_at' => now()->addMonth(),
                ]);
            }
        } elseif (in_array($transaction['status'], ['canceled', 'declined'], true)) {
            $payment->update([
                'status' => SubscriptionPayment::STATUS_FAILED,
                'metadata' => ['fedapay_status' => $transaction['status']],
            ]);
        }

        return response()->json(['received' => true]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Reglages de la plateforme depuis l'espace technique : plus besoin de la
 * console du serveur pour renouveler la cle de l'assistant ou saisir les
 * cles de paiement. Les secrets ne sont jamais renvoyes en clair a l'ecran.
 */
class TechniqueReglagesController extends Controller
{
    public function show(): Response
    {
        $saved = PlatformSetting::all_values();

        $fields = collect(PlatformSetting::FIELDS)->map(function ($def, $key) use ($saved) {
            $effective = (string) (config($def[1]) ?? '');

            return [
                'key' => $key,
                'label' => $def[0],
                'secret' => $def[2],
                'configured' => $effective !== '',
                'preview' => $effective === '' ? null : ($def[2] ? PlatformSetting::mask($effective) : $effective),
                'from_site' => array_key_exists($key, $saved),
            ];
        })->values();

        return Inertia::render('Technique/Reglages', ['fields' => $fields]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'values' => ['nullable', 'array'],
            'values.*' => ['nullable', 'string', 'max:500'],
            'clear' => ['nullable', 'array'],
            'clear.*' => ['string'],
        ]);

        $saved = 0;
        foreach ((array) $request->input('values', []) as $key => $value) {
            $value = trim((string) $value);
            if ($value === '' || ! array_key_exists($key, PlatformSetting::FIELDS)) {
                continue;
            }
            if ($key === 'fedapay_environment' && ! in_array($value, ['sandbox', 'live'], true)) {
                return back()->with('error', 'Le mode FedaPay doit être sandbox ou live.');
            }
            PlatformSetting::put($key, $value);
            $saved++;
        }

        foreach ((array) $request->input('clear', []) as $key) {
            if (array_key_exists($key, PlatformSetting::FIELDS)) {
                PlatformSetting::put($key, null);
                $saved++;
            }
        }

        return back()->with('success', $saved ? 'Réglages enregistrés.' : 'Rien à enregistrer.');
    }

    /** Petit appel de verification de la cle de l'assistant (quelques mots seulement). */
    public function testAssistant(): RedirectResponse
    {
        $key = config('assistant.api_key');
        if (! $key) {
            return back()->with('error', "Aucune clé n'est enregistrée pour l'assistant.");
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => config('assistant.api_version'),
                'content-type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('assistant.model'),
                'max_tokens' => 10,
                'messages' => [['role' => 'user', 'content' => 'Dis bonjour en un mot.']],
            ]);
        } catch (Throwable $e) {
            return back()->with('error', "Le serveur n'a pas pu joindre le service de l'assistant : ".$e->getMessage());
        }

        if ($response->successful()) {
            return back()->with('success', 'La clé fonctionne : l\'assistant répond.');
        }

        $detail = $response->json('error.message') ?: ('code '.$response->status());

        return back()->with('error', 'La clé est refusée ('.$detail.'). Enregistrez une nouvelle clé.');
    }
}

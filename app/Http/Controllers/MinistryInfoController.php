<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Informations officielles du ministere (2026-09-09) : ce qui figure sur
 * un dossier de reconnaissance de culte aupres du Ministere de l'Interieur
 * et de la Securite Publique - sigle, siege, coordonnees, numero
 * d'autorisation - plus le logo. Renseignables des la creation
 * (MinistryRegistrationController) mais tout facultatif a ce moment-la,
 * donc cet ecran sert aussi bien a completer un ministere cree en
 * libre-service qu'a renseigner ces champs pour un ministere plus ancien
 * qui n'existaient pas encore lors de sa creation (demo, ou cree avant cet
 * ajout). Meme droit et meme reserve a la racine (rang 0) que
 * HonorificTitlesController/SubscriptionController.
 */
class MinistryInfoController extends Controller
{
    public function edit(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($orgUnit->level_rank === OrgUnit::RANK_MINISTERE, 404);

        return Inertia::render('Settings/MinistryInfo', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'ministry' => $orgUnit->ministry->only([
                'id', 'name', 'acronym', 'registration_number',
                'headquarters_address', 'phone', 'email', 'website', 'logo_path',
            ]),
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($orgUnit->level_rank === OrgUnit::RANK_MINISTERE, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'acronym' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'headquarters_address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $ministry = $orgUnit->ministry;

        $data = [
            'name' => $validated['name'],
            'acronym' => $validated['acronym'] ?? null,
            'registration_number' => $validated['registration_number'] ?? null,
            'headquarters_address' => $validated['headquarters_address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
        ];

        if ($request->boolean('remove_logo') && $ministry->logo_path) {
            Storage::disk('public')->delete($ministry->logo_path);
            $data['logo_path'] = null;
        } elseif ($request->hasFile('logo')) {
            if ($ministry->logo_path) {
                Storage::disk('public')->delete($ministry->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('ministeres/logos', 'public');
        }

        $ministry->update($data);

        // Le nom ici est celui du MINISTERE (Ministry::name, l'identite
        // legale/officielle) - deliberement independant du nom de sa
        // racine (OrgUnit::name, l'entite organisationnelle), qui reste
        // gouverne par le flux "Transformer cette entité" existant
        // (OrgUnitTransformationController), seul endroit ou un renommage
        // d'OrgUnit est trace dans OrgUnitHistory (point 13). Les deux
        // partent de la meme valeur a la creation (voir
        // MinistryRegistrationService) mais peuvent diverger ensuite
        // sans que ce soit une anomalie.
        return redirect()
            ->route('ministry-info.edit', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Informations du ministère mises à jour.');
    }
}

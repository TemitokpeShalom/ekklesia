<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Services\AccountingStandardResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Chantier "module Finances" (2026-09-12, retour du ministere) : "il faut
 * mettre un bouton... pour choisir la norme comptable auquel la zone...
 * obeit". Avant ce controleur, la norme etait UNIQUEMENT deduite du pays
 * de l'entite (voir AccountingStandardResolver::countryUnitFor) - aucun
 * moyen de la choisir/forcer explicitement n'existait. Seules les normes
 * reellement documentees dans config('finance.standards') sont
 * proposables ici (aujourd'hui, seule SYSCOHADA) : jamais un code
 * fabrique sans documents reels, voir le doc-comment de ce fichier de
 * config a ce sujet.
 */
class AccountingStandardController extends Controller
{
    public function update(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);

        // Defensif : une chaine vide (jamais envoyee intentionnellement
        // par le selecteur, qui envoie null pour "automatique") doit
        // rester equivalente a "aucun choix", jamais rejetee par Rule::in.
        if ($request->input('standard') === '') {
            $request->merge(['standard' => null]);
        }

        $data = $request->validate([
            'standard' => ['nullable', 'string', Rule::in(array_keys(config('finance.standards')))],
        ]);

        $orgUnit->update(['accounting_standard_override' => $data['standard'] ?? null]);

        // Corrige le 2026-09-12 (retour du ministere, valable pour TOUS les
        // modules d'enregistrement) : confirmer clairement la reussite,
        // toujours en vert - jamais en rouge, reserve aux erreurs.
        $message = $data['standard']
            ? 'Norme comptable « '.AccountingStandardResolver::forOrgUnit($orgUnit->fresh())['label'].' » appliquée à cette entité (et ses niveaux descendants, sauf choix plus spécifique).'
            : 'Norme comptable réinitialisée : détection automatique par pays.';

        return redirect()->route('finances.index', ['orgUnit' => $orgUnit->id])->with('success', $message);
    }
}

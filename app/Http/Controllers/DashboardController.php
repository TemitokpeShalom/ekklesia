<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Premier tableau de bord, volontairement "vide" pour ce module Fondations
 * (point 10 - feuille de route) : il affiche le noeud courant et ses
 * enfants directs, rien de plus - les indicateurs metier arrivent avec
 * les modules suivants (Membres/cultes, Finances...).
 */
class DashboardController extends Controller
{
    public function show(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        return Inertia::render('Dashboard/Index', [
            // Corrige le 2026-09-12 (retour du ministere, valable pour TOUS
            // les niveaux) : "le retour doit être ramené progressivement
            // jusqu'au tableau de bord" - ce tableau de bord est celui d'un
            // niveau precis de la hierarchie ; sans parent_id, impossible
            // d'y placer un bouton "Retour" qui remonte au niveau parent.
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label', 'level_rank', 'code', 'parent_id']),
            // En-tete officiel du ministere (2026-09-11, voir Ministry::letterhead())
            // - visible des l'entree dans l'espace de travail, quel que soit
            // le niveau consulte (le ministere reste le meme).
            'ministry' => $orgUnit->ministry->letterhead(),
            'children' => $orgUnit->children()
                ->orderBy('name')
                ->get(['id', 'name', 'level_label', 'level_rank', 'code']),
            'activeAffectations' => $request->user()
                ->activeAffectations()
                ->with(['role:id,label', 'orgUnit:id,name'])
                ->get(),
            'canAccessLibrary' => $request->user()->hasPreachingAffectation(),
            'canManageAccess' => $request->user()->can('inviteTo', $orgUnit),
            'canTransform' => $request->user()->can('transform', $orgUnit),
        ]);
    }
}

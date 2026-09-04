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
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label', 'level_rank', 'code']),
            'children' => $orgUnit->children()
                ->orderBy('name')
                ->get(['id', 'name', 'level_label', 'level_rank', 'code']),
            'activeAffectations' => $request->user()
                ->activeAffectations()
                ->with(['role:id,label', 'orgUnit:id,name'])
                ->get(),
        ]);
    }
}

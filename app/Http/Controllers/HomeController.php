<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Correction du 2026-09-10 (trouvee en marge du chantier assistant IA) :
 * ce fichier ne contenait, depuis sa toute premiere version sur GitHub
 * (commit "Create HomeController.php", 9 septembre), que le code
 * d'InvitationController recopie a l'identique (class InvitationController
 * au lieu de class HomeController) - jamais corrige depuis, a la
 * difference d'InvitationController.php lui-meme qui avait recu le meme
 * type de correction le meme jour. Consequence : la route "/" (point 09 -
 * le logo Ekklesia y renvoie des qu'aucun orgUnit n'est en contexte, par
 * exemple depuis "Aide") plantait avec "Class HomeController not found".
 *
 * Reconstruit ici sur le meme principe que LoginController@store : trouver
 * l'affectation active a utiliser et rediriger vers son tableau de bord.
 * Priorite a une affectation dans le ministere DEJA en contexte
 * (current_ministry_id, ex. utilisateur multi-ministere qui vient de
 * "Aide"), puis a defaut la premiere affectation active tout court -
 * jamais de tableau de bord "vide" propre a ce controleur.
 */
class HomeController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $currentMinistryId = $request->session()->get('current_ministry_id');

        $affectation = $request->user()->activeAffectations()
            ->when($currentMinistryId, fn ($q) => $q->where('ministry_id', $currentMinistryId))
            ->first()
            ?? $request->user()->activeAffectations()->first();

        if (! $affectation) {
            return redirect()->route('welcome')
                ->with('error', "Aucune affectation active n'est associée à votre compte. Contactez votre responsable.");
        }

        // Garde le ministere en contexte coherent avec l'affectation choisie
        // (utile si l'utilisateur arrive ici depuis un contexte multi-
        // ministere different de celui deja fixe en session).
        $request->session()->put('current_ministry_id', $affectation->ministry_id);

        return redirect()->route('dashboard', ['orgUnit' => $affectation->org_unit_id]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * Point d'entree "/" (point 09) : jusqu'ici aucune route ne repondait a
 * cette adresse, alors que le logo Ekklesia de l'en-tete (AppLayout.vue)
 * y renvoie des qu'aucun orgUnit n'est en contexte (ecrans Aide, ex-
 * Assistant) - un clic dessus depuis ces pages tombait sur une 404. Cette
 * route renvoie simplement vers le tableau de bord le plus pertinent,
 * comme le fait deja LoginController@store juste apres la connexion.
 */
class HomeController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $firstAffectation = $request->user()->activeAffectations()->first();

        if (! $firstAffectation) {
            return redirect()->route('login');
        }

        return redirect()->route('dashboard', ['orgUnit' => $firstAffectation->org_unit_id]);
    }
}

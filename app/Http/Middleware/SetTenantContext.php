<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fixe app.current_ministry_id pour toute la requete, a partir du
 * ministere de l'utilisateur connecte (via son affectation active la
 * plus recente, ou celle explicitement choisie par un context-switcher
 * pour les personnes multi-affectees - point 09).
 *
 * C'est ce reglage que les policies RLS (voir la migration
 * enable_row_level_security) utilisent pour isoler chaque ministere -
 * point 04. Sans utilisateur connecte, ou sans ministere resolu, aucune
 * ligne des tables soumises a la RLS n'est visible : c'est volontaire.
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $ministryId = $request->session()->get('current_ministry_id');

        // set_config(..., true) borne le reglage a la transaction/requete
        // courante : rien ne "fuit" d'une requete a l'autre sur une
        // connexion reutilisee (pool de connexions).
        DB::statement('SELECT set_config(?, ?, true)', [
            'app.current_ministry_id',
            $ministryId ?? '',
        ]);

        return $next($request);
    }
}

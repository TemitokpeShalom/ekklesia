<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $ministryId = $request->session()->get('current_ministry_id');

        // Le "false" (au lieu de "true") fixe cette valeur pour toute la
        // duree de la connexion a la base, donc pour toute la duree de la
        // requete web (chaque requete obtient une connexion fraiche). Avec
        // "true", la valeur ne durait que le temps d'une seule requete SQL
        // et disparaissait avant que les requetes suivantes ne puissent la
        // lire.
        DB::statement('SELECT set_config(?, ?, false)', [
            'app.current_ministry_id',
            $ministryId ?? '',
        ]);

        DB::statement('SELECT set_config(?, ?, false)', [
            'app.current_user_id',
            $request->user()?->id ?? '',
        ]);

        return $next($request);
    }
}

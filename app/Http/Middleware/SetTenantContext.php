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

        DB::statement('SELECT set_config(?, ?, true)', [
            'app.current_ministry_id',
            $ministryId ?? '',
        ]);

        // Complement du point 04 : permet a un utilisateur deja connecte de
        // toujours retrouver ses propres affectations (policy
        // affectations_self_access), meme avant que le ministere courant
        // ne soit choisi.
        DB::statement('SELECT set_config(?, ?, true)', [
            'app.current_user_id',
            $request->user()?->id ?? '',
        ]);

        return $next($request);
    }
}

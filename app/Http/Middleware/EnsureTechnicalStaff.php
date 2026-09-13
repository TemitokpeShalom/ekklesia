<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reserve l'espace /technique (2026-09-13) aux membres de l'equipe
 * technique Oikonema - voir User::isTechnicalStaff(). Volontairement SANS
 * tenant.context : il n'y a pas de ministere "courant" ici, la vue est
 * transversale a tous les ministeres de la plateforme.
 */
class EnsureTechnicalStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isTechnicalStaff(), 403);

        return $next($request);
    }
}

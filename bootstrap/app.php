<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant.context' => SetTenantContext::class,
        ]);

        // Partage auth/flash a toutes les pages Inertia (point 09) ; le
        // contexte multi-tenant est fixe par tenant.context, applique aux
        // seules routes authentifiees (voir routes/web.php).
        $middleware->web(append: [HandleInertiaRequests::class]);

        // Le contexte multi-tenant (tenant.context) doit etre fixe AVANT
        // que Laravel ne resolve les parametres de route par liaison
        // implicite de modele (SubstituteBindings, ex. {orgUnit}), sinon
        // les policies RLS (point 04) bloquent silencieusement cette
        // resolution et produisent une fausse erreur 404 au lieu d'ouvrir
        // la page demandee.
        $middleware->appendToPriorityList(
            after: \Illuminate\Session\Middleware\StartSession::class,
            append: SetTenantContext::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

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

        // Ajoute le 2026-09-09 : par defaut Laravel envoie un visiteur non
        // connecte directement sur /connexion des qu'il touche une page
        // protegee - il n'existait alors aucune page expliquant les deux
        // parcours possibles ("creer un nouveau ministere" ou "se
        // connecter a un espace existant"). /bienvenue est cette page (voir
        // WelcomeController) ; son bouton "Se connecter" renvoie ensuite
        // normalement vers /connexion.
        $middleware->redirectGuestsTo('/bienvenue');

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

        // Point 15 : le webhook FedaPay est appele par les serveurs de
        // FedaPay eux-memes, qui ne peuvent fournir aucun jeton CSRF -
        // sa propre signature (verifiee dans SubscriptionFedapayController)
        // en tient lieu.
        $middleware->validateCsrfTokens(except: [
            'webhooks/fedapay',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

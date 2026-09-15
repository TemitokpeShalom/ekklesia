<?php

use App\Http\Middleware\EnsureTechnicalStaff;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant.context' => SetTenantContext::class,
            // Espace technique Oikonema (2026-09-13, voir EnsureTechnicalStaff) :
            // reserve un groupe de routes a l'equipe technique de la
            // plateforme, independamment de tout ministere.
            'technical.staff' => EnsureTechnicalStaff::class,
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
        // Page d'erreur habillee (2026-09-14, retour du ministere : "un
        // ecran noir... ca fait peur, on croit qu'il y a un probleme dans
        // la plateforme") - remplace la page Symfony brute par la page
        // Inertia Error.vue, a l'identite de la plateforme, pour les
        // erreurs HTTP les plus frequentes rencontrees par un utilisateur
        // (droits insuffisants, page introuvable, trop de tentatives,
        // erreur serveur). 419 (session/jeton CSRF expire) traite a part :
        // on revient simplement sur la page precedente avec un message
        // poli, plutot qu'un ecran d'erreur a part entiere.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if ($status === 419) {
                return back()->with('error', 'Votre session a expiré, probablement après un long moment sans activité. Merci de réessayer.');
            }

            if (in_array($status, [403, 404, 429, 500, 503], true)) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();

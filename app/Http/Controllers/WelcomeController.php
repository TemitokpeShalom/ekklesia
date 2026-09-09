<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Page d'entree publique (ajoutee le 2026-09-09).
 *
 * Avant, un visiteur non connecte qui arrivait sur l'application (ou qui se
 * deconnectait) tombait directement sur le formulaire de connexion, sans
 * jamais voir qu'il existait un autre parcours pour quelqu'un qui n'a pas
 * encore de compte : "il faut un bouton qui me permet d'accéder ... de
 * naviguer facilement entre les pages", et "il faut que ça puisse me
 * ramener sur la toute première page" apres deconnexion. Cette page offre
 * les deux points d'entree :
 * - se connecter a un espace de travail existant (/connexion) ;
 * - creer un nouveau ministere, en libre-service et sans validation
 *   manuelle (voir MinistryRegistrationController).
 *
 * Voir bootstrap/app.php (redirectGuestsTo) : c'est desormais vers cette
 * page, et non plus directement /connexion, que Laravel renvoie un
 * visiteur non connecte qui tente d'ouvrir une page protegee.
 */
class WelcomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Welcome');
    }
}

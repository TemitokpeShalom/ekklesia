<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manuel d'utilisation integre (point 09). Contenu stocke une seule fois
 * dans help_articles, affiche a deux endroits a partir des memes donnees :
 * - index() : le manuel complet, regroupe par module, pense pour etre
 *   imprime d'un bloc (formation en presentiel, export "PDF" via
 *   l'impression du navigateur, comme deja fait pour le trombinoscope) ;
 * - show() : une seule page, ouverte depuis le lien "Aide" d'un ecran
 *   precis, pour une consultation rapide en contexte.
 *
 * Accessible a tout utilisateur connecte, quel que soit son role : ce
 * n'est pas une donnee de ministere, juste la documentation du logiciel.
 * Ne depend donc d'aucun OrgUnit ni d'aucune policy.
 */
class HelpController extends Controller
{
    public function index(): Response
    {
        $articles = HelpArticle::orderBy('order')->get(['slug', 'module', 'title', 'body']);

        return Inertia::render('Aide/Index', [
            'modules' => $articles->groupBy('module')->map->values(),
        ]);
    }

    public function show(string $slug): Response
    {
        $article = HelpArticle::where('slug', $slug)->firstOrFail();

        $summary = HelpArticle::orderBy('order')->get(['slug', 'module', 'title']);

        return Inertia::render('Aide/Show', [
            'article' => $article,
            'modules' => $summary->groupBy('module')->map->values(),
        ]);
    }
}

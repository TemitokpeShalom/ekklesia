<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manuel d'utilisation integre (point 09) et recherche rapide (point 17,
 * anciennement AssistantController, fusionnes ici le 2026-09-09 : les deux
 * ecrans lisaient exactement la meme table help_articles sous deux noms
 * differents - « Assistant » et « Manuel d'utilisation » - ce qui creait
 * un doublon dans l'interface sans rien ajouter de fonctionnel). Contenu
 * stocke une seule fois, affiche a deux endroits a partir des memes
 * donnees :
 * - index() : le manuel complet, regroupe par module, et - si une
 *   recherche est en cours (parametre ?q=) - les articles les plus
 *   pertinents en tete, pense pour etre imprime d'un bloc (formation en
 *   presentiel, export "PDF" via l'impression du navigateur, comme deja
 *   fait pour le trombinoscope) ;
 * - show() : une seule page, ouverte depuis le lien "Aide" d'un ecran
 *   precis, pour une consultation rapide en contexte.
 *
 * La recherche reste un moteur par mots-cles, pas un modele de langage
 * externe : fonctionne sans cle API ni service tiers, et suffit a orienter
 * vers le bon article. Un vrai assistant conversationnel pourra la
 * remplacer plus tard sans changer l'ecran cote utilisateur (meme reponse
 * Inertia) - voir la feuille de route, point 17.
 *
 * Accessible a tout utilisateur connecte, quel que soit son role : ce
 * n'est pas une donnee de ministere, juste la documentation du logiciel.
 * Ne depend donc d'aucun OrgUnit ni d'aucune policy.
 */
class HelpController extends Controller
{
    private const MIN_WORD_LENGTH = 3;

    private const MAX_RESULTS = 3;

    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $articles = HelpArticle::orderBy('order')->get(['slug', 'module', 'title', 'body']);

        return Inertia::render('Aide/Index', [
            'modules' => $articles->groupBy('module')->map->values(),
            'query' => $query,
            'results' => $query !== '' ? $this->search($articles, $query) : [],
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

    private function search($articles, string $query): array
    {
        $words = collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY))
            ->unique()
            ->filter(fn ($word) => mb_strlen($word) >= self::MIN_WORD_LENGTH);

        if ($words->isEmpty()) {
            return [];
        }

        return $articles
            ->map(function (HelpArticle $article) use ($words) {
                $haystack = mb_strtolower($article->title.' '.$article->body);
                $score = $words->sum(fn ($word) => substr_count($haystack, $word));

                return ['article' => $article, 'score' => $score];
            })
            ->filter(fn ($scored) => $scored['score'] > 0)
            ->sortByDesc('score')
            ->take(self::MAX_RESULTS)
            ->map(fn ($scored) => [
                'slug' => $scored['article']->slug,
                'module' => $scored['article']->module,
                'title' => $scored['article']->title,
                'excerpt' => Str::limit(strip_tags($scored['article']->body), 220),
            ])
            ->values()
            ->all();
    }
}

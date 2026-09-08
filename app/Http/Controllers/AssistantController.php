<?php

namespace App\Http\Controllers;

use App\Models\HelpArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Assistant IA (point 17). Recherche par mots-cles sur le manuel integre
 * (point 09) plutot qu'un modele de langage externe : fonctionne sans cle
 * API ni service tiers, et suffit a orienter vers le bon article. Un vrai
 * assistant conversationnel pourra remplacer ce moteur de recherche plus
 * tard sans changer l'ecran cote utilisateur (meme reponse Inertia).
 *
 * Accessible a tout utilisateur connecte, comme le manuel lui-meme (voir
 * HelpController) : ce n'est pas une donnee de ministere.
 */
class AssistantController extends Controller
{
    private const MIN_WORD_LENGTH = 3;

    private const MAX_RESULTS = 3;

    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));

        return Inertia::render('Assistant/Index', [
            'query' => $query,
            'results' => $query !== '' ? $this->search($query) : [],
        ]);
    }

    private function search(string $query): array
    {
        $words = collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY))
            ->unique()
            ->filter(fn ($word) => mb_strlen($word) >= self::MIN_WORD_LENGTH);

        if ($words->isEmpty()) {
            return [];
        }

        return HelpArticle::orderBy('order')->get(['slug', 'module', 'title', 'body'])
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

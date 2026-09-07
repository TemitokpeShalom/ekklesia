<?php

namespace App\Http\Controllers;

use App\Models\Culte;
use App\Models\OrgUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bibliotheque ministerielle (point 08) : archive des messages preches,
 * logee au niveau Ministere (racine) - symetrique de la consolidation
 * (point 06) pour le perimetre (tout le ministere, jamais une portion),
 * mais l'acces ne suit pas la cascade habituelle des OrgUnitPolicy : il
 * est reserve a ceux qui prechent (Pasteur, a tout rang, y compris
 * cellule), jamais au Secretaire/Tresorier/Comptable meme responsable
 * de tout le ministere.
 */
class BibliothequeController extends Controller
{
    public function index(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);
        abort_unless($orgUnit->level_rank === OrgUnit::RANK_MINISTERE, 404);
        abort_unless($request->user()->hasPreachingAffectation(), 403);

        $search = trim((string) $request->query('q', ''));

        $messages = Culte::whereHas('orgUnit', function ($q) use ($orgUnit) {
            $q->whereRaw('org_units.path <@ ?::ltree', [$orgUnit->path]);
        })
            ->where('status', 'termine')
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($q) use ($like) {
                    $q->where('title', 'ilike', $like)
                        ->orWhere('speaker', 'ilike', $like)
                        ->orWhere('key_verses', 'ilike', $like)
                        ->orWhere('notes', 'ilike', $like);
                });
            })
            ->with('orgUnit:id,name,level_label')
            ->orderByDesc('service_date')
            ->limit(200)
            ->get();

        return Inertia::render('Bibliotheque/Index', [
            'orgUnit' => $orgUnit,
            'messages' => $messages,
            'search' => $search,
        ]);
    }
}

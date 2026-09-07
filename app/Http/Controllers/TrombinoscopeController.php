<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\OrgUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Trombinoscope (point 08, generateur de documents) : vue imprimable des
 * membres actifs d'un noeud et de ses descendants, avec photo. Meme
 * traversee d'arbre que les modules deja en place (Bibliotheque,
 * consolidation) : path <@ ce noeud. Lecture seule, donc simple droit de
 * vue (comme le tableau de bord lui-meme) plutot que manageMembers - tout
 * titulaire d'une affectation couvrant ce noeud peut l'imprimer.
 *
 * Les deux autres gabarits prevus a l'architecture (affiche, calendrier
 * 12 pages) restent a construire : ils dependent de contenus qui n'ont
 * pas encore de modele de donnees (evenements/dates cle du ministere), a
 * la difference du trombinoscope qui ne reutilise que ce qui existe deja.
 */
class TrombinoscopeController extends Controller
{
    private const MAX_MEMBERS = 500;

    public function index(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $members = Member::whereHas('orgUnit', function ($q) use ($orgUnit) {
            $q->whereRaw('org_units.path <@ ?::ltree', [$orgUnit->path]);
        })
            ->where('status', 'active')
            ->with('orgUnit:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::MAX_MEMBERS)
            ->get(['id', 'first_name', 'last_name', 'phone', 'photo_path', 'org_unit_id']);

        return Inertia::render('Trombinoscope/Index', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'members' => $members,
        ]);
    }
}

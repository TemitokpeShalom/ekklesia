<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Services\OrgUnitTransformationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Transformations organisationnelles (point 13) : renommage, promotion,
 * rattachement au sein du meme ministere.
 *
 * S'appuie sur OrgUnitTransformationService, deja present dans le socle
 * initial de l'application : promote() n'accepte pas de rang cible, elle
 * fait toujours avancer d'un cran suivant sa propre table de promotions
 * autorisees, et refuse avec une RuntimeException si le rang courant n'a
 * pas de promotion directe prevue (capturee ci-dessous et renvoyee comme
 * message d'erreur). Scission, fusion et fermeture ne sont pas traitees
 * ici : c'est deja le perimetre assume par le service lui-meme (voir son
 * commentaire d'en-tete), pas une limitation ajoutee par cet ecran.
 *
 * Il n'existe pas encore de flux de validation a deux temps ailleurs
 * dans l'application (chaque module agit immediatement des lors que
 * l'auteur est habilite) : requestedBy et approvedBy sont donc tous les
 * deux l'auteur de l'action.
 */
class OrgUnitTransformationController extends Controller
{
    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('transform', $orgUnit);

        $sameMinistry = OrgUnit::where('ministry_id', $orgUnit->ministry_id)
            ->where('id', '!=', $orgUnit->id)
            ->orderBy('path')
            ->get(['id', 'name', 'level_label', 'path']);

        $candidateParents = $sameMinistry
            ->reject(fn ($candidate) => str_starts_with($candidate->path, $orgUnit->path . '.'))
            ->values();

        return Inertia::render('OrgUnits/Transform', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label', 'level_rank', 'status', 'code']),
            'candidateParents' => $candidateParents,
            'history' => $orgUnit->history()
                ->orderByDesc('valid_from')
                ->get(['id', 'valid_from', 'valid_to', 'name', 'level_label', 'transformation_type', 'reason']),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit, OrgUnitTransformationService $service): RedirectResponse
    {
        $this->authorize('transform', $orgUnit);

        $validated = $request->validate([
            'transformation_type' => ['required', 'in:renommage,promotion,rattachement'],
            'reason' => ['required', 'string', 'max:1000'],
            'name' => ['required_if:transformation_type,renommage', 'string', 'max:255'],
            'new_parent_id' => ['required_if:transformation_type,rattachement', 'exists:org_units,id'],
        ]);

        $actor = $request->user();

        try {
            match ($validated['transformation_type']) {
                'renommage' => $service->rename($orgUnit, $validated['name'], $actor, $actor, $validated['reason']),
                'promotion' => $service->promote($orgUnit, $actor, $actor, $validated['reason']),
                'rattachement' => $service->reattach($orgUnit, OrgUnit::findOrFail($validated['new_parent_id']), $actor, $actor, $validated['reason']),
            };
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('org-units.transform.create', $orgUnit)->with('success', 'Transformation appliquée.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Services\OrgUnitTransformationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Transformations organisationnelles (point 13) : renommage, promotion,
 * rattachement au sein du meme ministere, et fermeture.
 *
 * Scission et fusion ne sont pas traitees ici pour l'instant : voir le
 * commentaire en tete de OrgUnitTransformationService pour la raison.
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
            'transformation_type' => ['required', 'in:renommage,promotion,rattachement,fermeture'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'name' => ['required_if:transformation_type,renommage', 'string', 'max:255'],
            'level_rank' => ['required_if:transformation_type,promotion', 'integer', 'min:0', 'max:6'],
            'level_label' => ['required_if:transformation_type,promotion', 'string', 'max:255'],
            'new_parent_id' => ['required_if:transformation_type,rattachement', 'exists:org_units,id'],
        ]);

        match ($validated['transformation_type']) {
            'renommage' => $service->rename($orgUnit, $validated['name'], $validated['reason'] ?? null, $request->user()),
            'promotion' => $service->promote($orgUnit, (int) $validated['level_rank'], $validated['level_label'], $validated['reason'] ?? null, $request->user()),
            'rattachement' => $service->reattach($orgUnit, OrgUnit::findOrFail($validated['new_parent_id']), $validated['reason'] ?? null, $request->user()),
            'fermeture' => $service->close($orgUnit, $validated['reason'] ?? null, $request->user()),
        };

        return redirect()->route('org-units.transform.create', $orgUnit)->with('success', 'Transformation appliquée.');
    }
}

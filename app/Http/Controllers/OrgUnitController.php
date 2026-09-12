<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Services\OrgUnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Création directe d'une entité enfant (retour du ministère, 2026-09-12,
 * remplace le mécanisme par code de rattachement, point 03) - controleur
 * volontairement mince (point 10) : il valide l'entrée, appelle le
 * service, renvoie une réponse.
 */
class OrgUnitController extends Controller
{
    public function __construct(private OrgUnitService $orgUnits)
    {
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('createChild', $orgUnit);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'level_rank' => ['required', 'integer', 'min:0', 'max:6'],
        ]);

        if ($validated['level_rank'] <= $orgUnit->level_rank) {
            return back()
                ->withErrors(['level_rank' => "Le niveau choisi doit être inférieur à celui de « {$orgUnit->name} »."])
                ->withInput();
        }

        try {
            $newUnit = $this->orgUnits->createChild(
                $orgUnit,
                $validated['name'],
                $validated['level_rank'],
                $request->user(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['level_rank' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('dashboard', ['orgUnit' => $orgUnit->id])
            ->with('success', "« {$newUnit->name} » a été créée et rattachée directement à « {$orgUnit->name} ».")
            ->with('created_org_unit_id', $newUnit->id)
            ->with('created_org_unit_name', $newUnit->name);
    }
}

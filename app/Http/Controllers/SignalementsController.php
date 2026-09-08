<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Signalement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Canal de signalement (point 17). Remontee bas-vers-haut symetrique de la
 * consolidation des rapports (points 06/18) : un signalement depose sur un
 * noeud est visible depuis ce noeud et n'importe lequel de ses ancetres,
 * jamais depuis un noeud non apparente - meme requete de chemin que
 * OrgUnit::scopeDescendantsOf, utilisee ici depuis le sens inverse
 * (l'ancetre consulte regarde vers ses descendants).
 */
class SignalementsController extends Controller
{
    public function index(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $signalements = Signalement::whereHas('orgUnit', function ($q) use ($orgUnit) {
            $q->whereRaw('org_units.path <@ ?::ltree', [$orgUnit->path]);
        })
            ->with(['orgUnit:id,name,level_label', 'submitter:id,name'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Signalement $s) => [
                'id' => $s->id,
                'category' => $s->category,
                'message' => $s->message,
                'status' => $s->status,
                'is_anonymous' => $s->is_anonymous,
                'submitter_name' => $s->is_anonymous ? null : $s->submitter?->name,
                'org_unit' => $s->orgUnit->only(['id', 'name', 'level_label']),
                'created_at' => $s->created_at->toIso8601String(),
            ]);

        return Inertia::render('Signalements/Index', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'signalements' => $signalements,
            'canManage' => $request->user()->can('manageSignalements', $orgUnit),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('view', $orgUnit);

        $data = $request->validate([
            'category' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:5000'],
            'is_anonymous' => ['nullable', 'boolean'],
        ]);

        $isAnonymous = $request->boolean('is_anonymous');

        $orgUnit->signalements()->create([
            'ministry_id' => $orgUnit->ministry_id,
            'category' => $data['category'],
            'message' => $data['message'],
            'is_anonymous' => $isAnonymous,
            'submitted_by' => $isAnonymous ? null : $request->user()->id,
            'status' => Signalement::STATUT_NOUVEAU,
        ]);

        return redirect()->route('signalements.index', ['orgUnit' => $orgUnit->id]);
    }

    public function updateStatus(Request $request, OrgUnit $orgUnit, Signalement $signalement): RedirectResponse
    {
        $this->authorize('manageSignalements', $orgUnit);

        abort_unless(
            OrgUnit::whereKey($signalement->org_unit_id)
                ->whereRaw('org_units.path <@ ?::ltree', [$orgUnit->path])
                ->exists(),
            404
        );

        $data = $request->validate([
            'status' => ['required', 'in:nouveau,en_cours,traite'],
        ]);

        $signalement->update($data);

        return redirect()->route('signalements.index', ['orgUnit' => $orgUnit->id]);
    }
}

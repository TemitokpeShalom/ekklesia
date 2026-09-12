<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\Sacrament;
use App\Services\DiscipleshipStageRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SacramentsController extends Controller
{
    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $sacraments = $orgUnit->sacraments()
            ->with(['member', 'spouseMember'])
            ->orderByDesc('event_date')
            ->get();

        return Inertia::render('Sacraments/Index', [
            'orgUnit' => $orgUnit,
            'sacraments' => $sacraments,
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageSacrements', $orgUnit);

        return Inertia::render('Sacraments/Create', [
            'orgUnit' => $orgUnit,
            'members' => $orgUnit->members()->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageSacrements', $orgUnit);

        $data = $this->validateSacrament($request);

        $sacrement = $orgUnit->sacraments()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
        ]);

        $this->syncDiscipleshipForBaptism($sacrement);

        // Corrige le 2026-09-12 (retour du ministere, valable pour TOUS les
        // modules d'enregistrement) : confirmer clairement la reussite,
        // toujours en vert - jamais en rouge, reserve aux erreurs.
        return redirect()->route('sacrements.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Sacrement enregistré.');
    }

    public function edit(OrgUnit $orgUnit, Sacrament $sacrement): Response
    {
        $this->authorize('manageSacrements', $orgUnit);
        abort_unless($sacrement->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Sacraments/Edit', [
            'orgUnit' => $orgUnit,
            'sacrement' => $sacrement->load(['member', 'spouseMember']),
            'members' => $orgUnit->members()->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, Sacrament $sacrement): RedirectResponse
    {
        $this->authorize('manageSacrements', $orgUnit);
        abort_unless($sacrement->org_unit_id === $orgUnit->id, 404);

        $data = $this->validateSacrament($request);

        $sacrement->update($data);

        $this->syncDiscipleshipForBaptism($sacrement);

        return redirect()->route('sacrements.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Sacrement mis à jour.');
    }

    public function destroy(OrgUnit $orgUnit, Sacrament $sacrement): RedirectResponse
    {
        $this->authorize('manageSacrements', $orgUnit);
        abort_unless($sacrement->org_unit_id === $orgUnit->id, 404);

        $sacrement->delete();

        return redirect()->route('sacrements.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Sacrement supprimé.');
    }

    /**
     * Corrige le 2026-09-12 (retour du ministere, module Parcours de
     * disciple) : "dès que le baptême est réalisé pour une personne, le
     * système reconnaît en même temps [et] actualise son niveau
     * d'avancement" - evite un aller-retour manuel dans Parcours de
     * disciple pour cocher "Baptisé" alors que l'information est deja
     * saisie ici. Ne s'applique que si le membre principal est un membre
     * reellement enregistre (member_id) : un simple nom libre (personne pas
     * encore enregistree) n'a pas de fiche Parcours de disciple a mettre a
     * jour. La date de l'etape suit celle du bapteme (event_date), y
     * compris si elle est corrigee plus tard via update().
     */
    private function syncDiscipleshipForBaptism(Sacrament $sacrement): void
    {
        if ($sacrement->type !== 'bapteme' || ! $sacrement->member_id) {
            return;
        }

        $member = $sacrement->member ?? $sacrement->member()->first();

        if ($member) {
            DiscipleshipStageRecorder::record($member, 'baptise', $sacrement->event_date?->toDateString());
        }
    }

    /**
     * Corrige le 2026-09-12 (retour du ministere) : le membre principal
     * (baptise, ou premier conjoint d'un mariage) n'est pas toujours deja
     * enregistre sur la plateforme - "je ne veux pas que ça soit
     * faussement un membre". member_id (liste deroulante) et member_name
     * (saisie libre) sont donc tous deux facultatifs a la validation,
     * exactement comme spouse_member_id/spouse_name pour le second
     * conjoint, mais l'un des deux doit etre renseigne : un sacrement
     * concerne forcement quelqu'un.
     */
    private function validateSacrament(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:bapteme,mariage'],
            'member_id' => ['nullable', 'uuid', 'exists:members,id'],
            'member_name' => ['nullable', 'string', 'max:255'],
            'spouse_member_id' => ['nullable', 'uuid', 'exists:members,id'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'officiant' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        abort_if(
            empty($data['member_id']) && empty($data['member_name']),
            422,
            'Indiquez le membre concerné, ou à défaut son nom.'
        );

        // Les deux ne devraient jamais etre remplis a la fois en pratique
        // (comme pour le conjoint) - si un membre est selectionne, le nom
        // libre est ignore pour eviter toute incoherence entre les deux.
        if (! empty($data['member_id'])) {
            $data['member_name'] = null;
        }

        return $data;
    }
}

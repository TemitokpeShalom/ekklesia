<?php

namespace App\Http\Controllers;

use App\Models\Culte;
use App\Models\OrgUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CultesController extends Controller
{
    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $cultes = $orgUnit->cultes()->orderByDesc('service_date')->get();

        return Inertia::render('Cultes/Index', [
            'orgUnit' => $orgUnit,
            'cultes' => $cultes,
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageCultes', $orgUnit);

        return Inertia::render('Cultes/Create', [
            'orgUnit' => $orgUnit,
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageCultes', $orgUnit);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'service_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'speaker' => ['nullable', 'string', 'max:255'],
            'key_verses' => ['nullable', 'string'],
            'attendance_adults' => ['nullable', 'integer', 'min:0'],
            'attendance_children' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        // Corrige le 2026-09-11 (retour du ministere) : un culte etait
        // toujours cree "Planifie", meme quand sa date etait deja passee
        // (ex. saisie retroactive d'un culte du mois dernier, avec ses
        // presences deja connues) - or seuls les cultes "Termine" alimentent
        // la Bibliotheque (voir BibliothequeController), donc un tel culte
        // restait invisible tant que personne ne pensait a aller changer
        // manuellement son statut depuis l'ecran Modifier. Un culte dont la
        // date est aujourd'hui ou deja passee est donc desormais cree
        // directement "Termine" ; seule une date future reste "Planifie"
        // (rien n'a encore eu lieu, il n'y a donc rien a archiver).
        $status = $data['service_date'] <= now()->toDateString() ? 'termine' : 'planifie';

        $orgUnit->cultes()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
            'status' => $status,
        ]);

        return redirect()->route('cultes.index', ['orgUnit' => $orgUnit->id]);
    }

    public function edit(OrgUnit $orgUnit, Culte $culte): Response
    {
        $this->authorize('manageCultes', $orgUnit);
        abort_unless($culte->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Cultes/Edit', [
            'orgUnit' => $orgUnit,
            'culte' => $culte,
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, Culte $culte): RedirectResponse
    {
        $this->authorize('manageCultes', $orgUnit);
        abort_unless($culte->org_unit_id === $orgUnit->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'service_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'speaker' => ['nullable', 'string', 'max:255'],
            'key_verses' => ['nullable', 'string'],
            'attendance_adults' => ['nullable', 'integer', 'min:0'],
            'attendance_children' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:planifie,termine,annule'],
        ]);

        $culte->update($data);

        return redirect()->route('cultes.index', ['orgUnit' => $orgUnit->id]);
    }

    public function destroy(OrgUnit $orgUnit, Culte $culte): RedirectResponse
    {
        $this->authorize('manageCultes', $orgUnit);
        abort_unless($culte->org_unit_id === $orgUnit->id, 404);

        $culte->delete();

        return redirect()->route('cultes.index', ['orgUnit' => $orgUnit->id]);
    }
}

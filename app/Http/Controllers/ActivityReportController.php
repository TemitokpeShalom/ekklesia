<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityReportController extends Controller
{
    public function edit(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $month = $this->resolveMonth($request);
        $period = $month.'-01';
        $end = date('Y-m-t', strtotime($period));

        $report = $orgUnit->activityReports()->where('period', $period)->first();

        $cultes = $orgUnit->cultes()
            ->whereBetween('service_date', [$period, $end])
            ->orderBy('service_date')
            ->get(['id', 'title', 'service_date', 'attendance_adults', 'attendance_children']);

        // Retire le 2026-09-11 (retour du ministere) : un total d'effectifs
        // obtenu en additionnant "attendance_adults"/"attendance_children"
        // sur plusieurs cultes du mois compte plusieurs fois une meme
        // personne presente a plus d'un culte - ce total n'a jamais de sens
        // et ne doit plus etre calcule ni envoye a la vue. Seul le detail
        // culte par culte (deja dans $cultes) est affiche desormais.
        return Inertia::render('Activites/Rapport', [
            'orgUnit' => $orgUnit,
            'month' => $month,
            // En-tete officiel du ministere (2026-09-11, voir
            // Ministry::letterhead()) - ce rapport est un document, il porte
            // desormais l'identite du ministere comme un en-tete de courrier.
            'ministry' => $orgUnit->ministry->letterhead(),
            // Bloc "position" (retour du ministere, 2026-09-12 : meme
            // demande que pour le rapport financier - voir
            // FinanceReportController::show()).
            'ancestry' => $orgUnit->ancestryChain(),
            'pastorName' => $orgUnit->pastorName(),
            'report' => $report ? [
                ...$report->toArray(),
                'validator_name' => $report->isValidated() ? $report->validator?->name : null,
            ] : null,
            'cultes' => $cultes,
            // Corrige le 2026-09-12 (retour du ministere : "le nombre de
            // baptemes renseignes dans l'application doit pouvoir afficher
            // la automatiquement") - calcule ici depuis le module
            // Sacrements (jamais ressaisi separement), voir update()
            // ci-dessous pour la meme regle a l'enregistrement.
            'baptismsAuto' => $this->baptismsFromSacraments($orgUnit, $period, $end),
            'canManage' => $request->user()->can('manageCultes', $orgUnit),
        ]);
    }

    private function baptismsFromSacraments(OrgUnit $orgUnit, string $period, string $end): int
    {
        return $orgUnit->sacraments()
            ->where('type', 'bapteme')
            ->whereBetween('event_date', [$period, $end])
            ->count();
    }

    public function update(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageCultes', $orgUnit);

        $month = $this->resolveMonth($request, 'month');

        $this->abortIfValidated($orgUnit, $month);

        $data = $request->validate([
            'new_converts_count' => ['nullable', 'integer', 'min:0'],
            'activities_notes' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'leader_notes' => ['nullable', 'string'],
        ]);

        // Corrige le 2026-09-12 (retour du ministere) : "le nombre de
        // baptemes renseignes dans l'application doit pouvoir afficher la
        // automatiquement" - baptisms_count n'est plus saisi a la main,
        // il est recalcule ici depuis le module Sacrements a chaque
        // enregistrement (comme le reste du rapport, il se fige une fois
        // le mois valide - voir abortIfValidated ci-dessus).
        $period = $month.'-01';
        $end = date('Y-m-t', strtotime($period));
        $data['baptisms_count'] = $this->baptismsFromSacraments($orgUnit, $period, $end);

        $orgUnit->activityReports()->updateOrCreate(
            ['period' => $period],
            $data + ['ministry_id' => $orgUnit->ministry_id]
        );

        // Corrige le 2026-09-12 (retour du ministere, valable pour TOUS les
        // modules d'enregistrement) : confirmer clairement la reussite,
        // toujours en vert - jamais en rouge, reserve aux erreurs. Ce
        // controleur avait ete oublie lors du passage precedent.
        return redirect()->route('activites.rapport', ['orgUnit' => $orgUnit->id, 'mois' => $month])
            ->with('success', 'Rapport enregistré.');
    }

    /**
     * Chantier "module Documents" (2026-09-12, retour du ministere) : un
     * rapport valide devient un document officiel - il ne se re-ecrit plus
     * en silence (voir Ministry::letterhead(), RapportsArchiveController).
     * Deverrouiller reste possible pour une vraie correction (unlock()
     * ci-dessous), volontairement pas une simple case a cocher perdue dans
     * le formulaire.
     */
    public function validateReport(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageCultes', $orgUnit);

        $month = $this->resolveMonth($request, 'month');
        $report = $orgUnit->activityReports()->where('period', $month.'-01')->first();

        abort_if(! $report, 404, "Aucun rapport enregistre pour ce mois - rien a valider.");

        $report->update(['validated_at' => now(), 'validated_by' => $request->user()->id]);

        return redirect()->route('activites.rapport', ['orgUnit' => $orgUnit->id, 'mois' => $month])
            ->with('success', 'Rapport validé et archivé.');
    }

    public function unlock(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageCultes', $orgUnit);

        $month = $this->resolveMonth($request, 'month');

        $orgUnit->activityReports()->where('period', $month.'-01')
            ->update(['validated_at' => null, 'validated_by' => null]);

        return redirect()->route('activites.rapport', ['orgUnit' => $orgUnit->id, 'mois' => $month])
            ->with('success', 'Rapport déverrouillé : vous pouvez le corriger puis le valider à nouveau.');
    }

    private function abortIfValidated(OrgUnit $orgUnit, string $month): void
    {
        $isValidated = $orgUnit->activityReports()
            ->where('period', $month.'-01')
            ->whereNotNull('validated_at')
            ->exists();

        abort_if($isValidated, 422, 'Ce rapport est validé et verrouillé : déverrouillez-le d\'abord pour le corriger.');
    }

    private function resolveMonth(Request $request, string $field = 'mois'): string
    {
        $month = $field === 'mois' ? $request->query($field, now()->format('Y-m')) : $request->input($field, now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : now()->format('Y-m');
    }
}

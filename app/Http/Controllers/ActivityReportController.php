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

        return Inertia::render('Activites/Rapport', [
            'orgUnit' => $orgUnit,
            'month' => $month,
            'report' => $report,
            'cultes' => $cultes,
            'effectifs' => [
                'adultes' => (int) $cultes->sum('attendance_adults'),
                'enfants' => (int) $cultes->sum('attendance_children'),
            ],
            'canManage' => $request->user()->can('manageCultes', $orgUnit),
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageCultes', $orgUnit);

        $month = $this->resolveMonth($request, 'month');

        $data = $request->validate([
            'baptisms_count' => ['nullable', 'integer', 'min:0'],
            'new_converts_count' => ['nullable', 'integer', 'min:0'],
            'activities_notes' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'leader_notes' => ['nullable', 'string'],
        ]);

        $orgUnit->activityReports()->updateOrCreate(
            ['period' => $month.'-01'],
            $data + ['ministry_id' => $orgUnit->ministry_id]
        );

        return redirect()->route('activites.rapport', ['orgUnit' => $orgUnit->id, 'mois' => $month]);
    }

    private function resolveMonth(Request $request, string $field = 'mois'): string
    {
        $month = $field === 'mois' ? $request->query($field, now()->format('Y-m')) : $request->input($field, now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : now()->format('Y-m');
    }
}

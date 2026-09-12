<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\OrgUnit;
use App\Support\FrenchCalendar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;

/**
 * Generateur de documents (reliquat du point 08, revu le 2026-09-12). Ce
 * hub ne regroupe plus que l'Affiche et le Calendrier annuel : le
 * Trombinoscope est devenu un module a part entiere du tableau de bord
 * (retour du ministere - "pour moi le role de ce grand module document
 * reste incompris... garder le trombinoscope comme un module a part
 * entiere hors du module document"), et l'archive des rapports valides vit
 * desormais dans RapportsArchiveController (sous-module "Rapports").
 */
class DocumentGeneratorController extends Controller
{
    private const MAX_MEMBERS = 500;

    private const TEMPLATES = ['affiche'];

    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        return Inertia::render('Documents/Generateur', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
        ]);
    }

    public function show(Request $request, OrgUnit $orgUnit, string $template): Response
    {
        $this->authorize('view', $orgUnit);
        abort_unless(in_array($template, self::TEMPLATES, true), 404);

        $members = Member::whereHas('orgUnit', function ($q) use ($orgUnit) {
            $q->whereRaw('org_units.path <@ ?::ltree', [$orgUnit->path]);
        })
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::MAX_MEMBERS)
            ->get(['id', 'first_name', 'last_name', 'title', 'phone', 'birth_date', 'org_unit_id']);

        $leaders = $members
            ->filter(fn ($m) => filled($m->title))
            ->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'name' => "{$m->first_name} {$m->last_name}",
            ])
            ->values();

        return Inertia::render('Documents/Apercu', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'ministry' => $orgUnit->ministry->only(['id', 'name']),
            'template' => $template,
            'leaders' => $leaders,
            'memberCount' => $members->count(),
        ]);
    }

    /**
     * Calendrier annuel (refait le 2026-09-12, retour du ministere : "je
     * voulais un calendrier normal avec les trente, trente-et-un jours du
     * mois comme tout autre calendrier... et aussi renseigner le pasteur
     * sur les evenements de chaque jour, si c'est l'anniversaire d'un
     * fidele"). Vraie grille de 12 mois (jours 1..N, alignes sur le bon
     * jour de la semaine), pas seulement une liste triee comme avant.
     * S'appuie toujours sur birth_date (voir doc-comment historique
     * ci-dessous, valable pour le principe general meme si le gabarit a
     * change) plutot qu'un nouveau modele "evenements".
     */
    public function calendrier(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $members = Member::whereHas('orgUnit', function ($q) use ($orgUnit) {
            $q->whereRaw('org_units.path <@ ?::ltree', [$orgUnit->path]);
        })
            ->where('status', 'active')
            ->whereNotNull('birth_date')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::MAX_MEMBERS)
            ->get(['id', 'first_name', 'last_name', 'title', 'phone', 'birth_date']);

        $year = (int) now()->year;

        $months = collect(FrenchCalendar::MOIS)->map(function ($label, $num) use ($members, $year) {
            $firstOfMonth = Carbon::create($year, $num, 1);
            $daysInMonth = $firstOfMonth->daysInMonth;
            // ISO : 1 = lundi ... 7 = dimanche, aligne sur FrenchCalendar::JOURS.
            $leadingBlanks = $firstOfMonth->dayOfWeekIso - 1;

            $byDay = $members
                ->filter(fn ($m) => (int) $m->birth_date->format('n') === $num)
                ->groupBy(fn ($m) => (int) $m->birth_date->format('j'));

            $days = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $days[] = [
                    'day' => $d,
                    'birthdays' => ($byDay->get($d) ?? collect())->map(fn ($m) => [
                        'id' => $m->id,
                        'first_name' => $m->first_name,
                        'name' => trim("{$m->title} {$m->first_name} {$m->last_name}"),
                        'phone' => $m->phone,
                    ])->values(),
                ];
            }

            return [
                'number' => $num,
                'label' => $label,
                'leadingBlanks' => $leadingBlanks,
                'days' => $days,
            ];
        })->values();

        return Inertia::render('Documents/Calendrier', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'ministry' => $orgUnit->ministry->letterhead(),
            'year' => $year,
            'months' => $months,
            'joursSemaine' => FrenchCalendar::JOURS,
        ]);
    }
}

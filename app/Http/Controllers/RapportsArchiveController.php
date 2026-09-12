<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AssetsController;
use App\Models\AssetInventoryValidation;
use App\Models\FinancialReportValidation;
use App\Models\OrgUnit;
use App\Services\AccountingStandardResolver;
use App\Services\FinanceReportBuilder;
use App\Support\FrenchCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sous-module "Rapports" de Documents (chantier 2026-09-12, retour du
 * ministere : "je voulais... trouver tous les differents rapports ranges
 * mois par mois... et des que vous cliquez sur un mois, vous avez le
 * rapport directement"). Un mois n'apparait ici comme consultable/imprimable
 * qu'une fois VALIDE (voir ActivityReportController::validateReport et
 * FinanceReportController::validateReport) - ce module est une archive de
 * documents officiels, jamais un raccourci vers un rapport encore en
 * brouillon.
 */
class RapportsArchiveController extends Controller
{
    // Nombre de mois recents proposes dans la liste - au-dela, un
    // ministere tres ancien devra un jour faire defiler, mais ce n'est pas
    // demande pour l'instant et 36 mois couvre largement un usage normal.
    private const MOIS_AFFICHES = 36;

    // Nombre d'annees recentes proposees pour l'inventaire - un document
    // annuel, contrairement aux rapports mensuels ci-dessus ; 10 ans
    // couvre largement un usage normal sans faire defiler pour rien.
    private const ANNEES_AFFICHEES = 10;

    public function __construct(
        private FinanceReportBuilder $reportBuilder,
        private AssetsController $assetsController,
    ) {
    }

    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $activityValidations = $orgUnit->activityReports()
            ->whereNotNull('validated_at')
            ->pluck('validated_at', 'period')
            ->mapWithKeys(fn ($at, $period) => [Carbon::parse($period)->format('Y-m') => $at]);

        $financeValidations = FinancialReportValidation::where('org_unit_id', $orgUnit->id)
            ->pluck('validated_at', 'period')
            ->mapWithKeys(fn ($at, $period) => [Carbon::parse($period)->format('Y-m') => $at]);

        $months = collect(range(0, self::MOIS_AFFICHES - 1))->map(function ($i) use ($activityValidations, $financeValidations) {
            $date = now()->subMonthsNoOverflow($i);
            $key = $date->format('Y-m');

            return [
                'period' => $key,
                'label' => FrenchCalendar::MOIS[(int) $date->format('n')].' '.$date->format('Y'),
                'activityValidatedAt' => $activityValidations->get($key),
                'financeValidatedAt' => $financeValidations->get($key),
            ];
        })->values();

        // Chantier "module Inventaire" (2026-09-12, retour du ministere) :
        // "c'est un document qui manque" - une fiche d'inventaire validee
        // pour une annee doit se retrouver ici, au meme titre que les
        // rapports mensuels, meme si sa cadence est annuelle et non
        // mensuelle (voir AssetInventoryValidation).
        $inventoryValidations = AssetInventoryValidation::where('org_unit_id', $orgUnit->id)
            ->pluck('validated_at', 'year');

        $inventoryYears = collect(range(0, self::ANNEES_AFFICHEES - 1))
            ->map(fn ($i) => (int) now()->format('Y') - $i)
            ->map(fn ($year) => [
                'year' => $year,
                'validatedAt' => $inventoryValidations->get($year),
            ])
            ->values();

        return Inertia::render('Documents/Rapports', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'months' => $months,
            'inventoryYears' => $inventoryYears,
            'childReports' => $this->recentChildReports($orgUnit),
        ]);
    }

    /**
     * Chantier "module Finances" (2026-09-12, retour du ministere) :
     * "des qu'il valide... le message... remonte au niveau haut" - le
     * niveau juste au-dessus doit voir, sans avoir a aller chercher,
     * quelles entites filles ont recemment transmis un rapport valide,
     * avec un lien direct vers son PDF (deja consultable, la meme
     * cascade de droits que le reste de la plateforme - voir
     * OrgUnitPolicy::view - laisse un ancetre ouvrir la page d'un
     * descendant). Limite aux enfants DIRECTS (le niveau juste en
     * dessous, pas tout le sous-arbre) : une Region n'a pas besoin de
     * voir defiler chaque Cellule de chaque Eglise d'un coup, seulement
     * ses Districts. Fenetre de 60 jours, sans marquage lu/non-lu pour
     * l'instant (voir LISEZ-MOI de cette livraison) - suffisant pour
     * "etre notifie" sans construire un systeme de notifications a part
     * qui n'existe nulle part ailleurs dans l'application.
     */
    private function recentChildReports(OrgUnit $orgUnit): array
    {
        $childIds = $orgUnit->children()->pluck('id', 'id');
        if ($childIds->isEmpty()) {
            return [];
        }

        $since = now()->subDays(60);

        $activity = $orgUnit->children()
            ->whereHas('activityReports', fn ($q) => $q->whereNotNull('validated_at')->where('validated_at', '>=', $since))
            ->with(['activityReports' => fn ($q) => $q->whereNotNull('validated_at')->where('validated_at', '>=', $since)->with('validator:id,name')])
            ->get()
            ->flatMap(fn ($child) => $child->activityReports->map(fn ($report) => [
                'type' => 'activite',
                'org_unit' => $child->only(['id', 'name', 'level_label']),
                'period' => Carbon::parse($report->period)->format('Y-m'),
                'validated_at' => $report->validated_at,
                'validator_name' => $report->validator?->name,
            ]));

        $finance = FinancialReportValidation::whereIn('org_unit_id', $childIds)
            ->where('validated_at', '>=', $since)
            ->with(['orgUnit:id,name,level_label', 'validator:id,name'])
            ->get()
            ->map(fn ($validation) => [
                'type' => 'finance',
                'org_unit' => $validation->orgUnit->only(['id', 'name', 'level_label']),
                'period' => Carbon::parse($validation->period)->format('Y-m'),
                'validated_at' => $validation->validated_at,
                'validator_name' => $validation->validator?->name,
            ]);

        return $activity->concat($finance)
            ->sortByDesc('validated_at')
            ->values()
            ->all();
    }

    public function activite(OrgUnit $orgUnit, string $period): Response
    {
        $this->authorize('view', $orgUnit);
        $this->abortUnlessValidPeriod($period);

        $report = $orgUnit->activityReports()->where('period', $period.'-01')->whereNotNull('validated_at')->with('validator:id,name')->first();
        abort_unless($report, 404, "Aucun rapport d'activités validé pour ce mois.");

        $end = date('Y-m-t', strtotime($period.'-01'));
        $cultes = $orgUnit->cultes()
            ->whereBetween('service_date', [$period.'-01', $end])
            ->orderBy('service_date')
            ->get(['id', 'title', 'service_date', 'attendance_adults', 'attendance_children']);

        return Inertia::render('Documents/RapportActiviteArchive', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'ministry' => $orgUnit->ministry->letterhead(),
            'ancestry' => $orgUnit->ancestryChain(),
            'pastorName' => $orgUnit->pastorName(),
            'period' => $period,
            'monthLabel' => FrenchCalendar::MOIS[(int) date('n', strtotime($period.'-01'))].' '.date('Y', strtotime($period.'-01')),
            'report' => $report,
            'cultes' => $cultes,
        ]);
    }

    public function finance(OrgUnit $orgUnit, string $period): Response
    {
        $this->authorize('view', $orgUnit);
        $this->abortUnlessValidPeriod($period);

        $validation = FinancialReportValidation::where('org_unit_id', $orgUnit->id)
            ->where('period', $period.'-01')
            ->with('validator:id,name')
            ->first();
        abort_unless($validation, 404, 'Aucun rapport financier validé pour ce mois.');

        $start = $period.'-01';
        $end = date('Y-m-t', strtotime($start));

        return Inertia::render('Documents/RapportFinanceArchive', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'ministry' => $orgUnit->ministry->letterhead(),
            'ancestry' => $orgUnit->ancestryChain(),
            'pastorName' => $orgUnit->pastorName(),
            'period' => $period,
            'monthLabel' => FrenchCalendar::MOIS[(int) date('n', strtotime($start))].' '.date('Y', strtotime($start)),
            'devises' => $this->reportBuilder->build($orgUnit, $start, $end),
            'validation' => ['validated_at' => $validation->validated_at, 'validator_name' => $validation->validator?->name],
        ]);
    }

    public function inventaire(OrgUnit $orgUnit, string $year): Response
    {
        $this->authorize('view', $orgUnit);
        abort_unless((bool) preg_match('/^\d{4}$/', $year), 404);

        $validation = AssetInventoryValidation::where('org_unit_id', $orgUnit->id)
            ->where('year', (int) $year)
            ->with('validator:id,name')
            ->first();
        abort_unless($validation, 404, "Aucune fiche d'inventaire validée pour cette année.");

        return Inertia::render('Documents/InventaireArchive', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'ministry' => $orgUnit->ministry->letterhead(),
            'ancestry' => $orgUnit->ancestryChain(),
            'pastorName' => $orgUnit->pastorName(),
            'year' => (int) $year,
            'parCategorie' => $this->assetsController->consolidatedAssets($orgUnit, "{$year}-12-31"),
            'currency' => AccountingStandardResolver::currencyFor($orgUnit),
            'validation' => ['validated_at' => $validation->validated_at, 'validator_name' => $validation->validator?->name],
        ]);
    }

    private function abortUnlessValidPeriod(string $period): void
    {
        abort_unless((bool) preg_match('/^\d{4}-\d{2}$/', $period), 404);
    }
}

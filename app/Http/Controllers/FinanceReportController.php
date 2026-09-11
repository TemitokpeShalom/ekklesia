<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\OrgUnit;
use App\Services\AccountingStandardResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class FinanceReportController extends Controller
{
    /** Libelles universels (point 18) utilises quand le pays de l'org_unit n'a pas encore de norme documentee. */
    private const TYPE_LABELS = [
        'dime' => 'Dîmes',
        'offrande' => 'Offrandes',
        'action_de_grace' => 'Action de grâce',
        'don' => 'Dons',
        'depense' => 'Dépenses',
    ];

    /**
     * Point 08 (rapports consolidés multidevises) : vue consolidée = les
     * mouvements propres a ce noeud, plus ceux de tous ses descendants
     * (meme regle « activite propre » que les effectifs/l'inventaire),
     * jamais uniquement ce seul noeud. Comme le ministere pilote est reparti
     * sur plusieurs pays a devises differentes, et qu'aucune conversion de
     * change n'existe dans l'application (meme principe deja applique a
     * l'inventaire, point 19), le rapport ne totalise jamais deux devises
     * ensemble : chaque devise rencontree obtient son propre bloc, avec son
     * propre detail et son propre solde.
     */
    public function show(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $month = $request->query('mois', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = $month.'-01';
        $end = date('Y-m-t', strtotime($start));

        $orgUnitIds = OrgUnit::descendantsOf($orgUnit)->pluck('id');

        $transactions = FinancialTransaction::whereIn('org_unit_id', $orgUnitIds)
            ->whereBetween('transaction_date', [$start, $end])
            ->with('orgUnit')
            ->orderBy('transaction_date')
            ->get();

        $devises = $transactions
            ->groupBy('currency')
            ->map(fn ($group, $currency) => $this->buildDeviseBlock($currency, $group))
            ->sortKeys()
            ->values();

        return Inertia::render('Finances/Rapport', [
            'orgUnit' => $orgUnit,
            'month' => $month,
            // En-tete officiel du ministere (2026-09-11, voir
            // Ministry::letterhead()) - ce rapport est un document, il porte
            // desormais l'identite du ministere comme un en-tete de courrier.
            'ministry' => $orgUnit->ministry->letterhead(),
            'devises' => $devises,
        ]);
    }

    /**
     * Le detail par compte comptable n'a de sens que si TOUTES les lignes
     * de cette devise viennent du meme plan de comptes (account_code deja
     * renseigne a la saisie, voir FinanceTransactionsController) - des que
     * la devise melange des mouvements avec et sans compte comptable, on
     * retombe sur la seule nature universelle (type), jamais un compte
     * invente ou mélangé entre deux normes.
     */
    private function buildDeviseBlock(string $currency, Collection $group): array
    {
        $hasStandard = $group->every(fn ($t) => $t->account_code !== null);
        $groupKey = $hasStandard ? 'account_code' : 'type';

        $encaissements = $this->groupLines($group->where('nature', 'encaissement'), $groupKey, $hasStandard);
        $decaissements = $this->groupLines($group->where('nature', 'decaissement'), $groupKey, $hasStandard);

        $totalEncaissements = $encaissements->sum('total');
        $totalDecaissements = $decaissements->sum('total');

        $standard = $hasStandard ? AccountingStandardResolver::forOrgUnit($group->first()->orgUnit) : null;

        return [
            'currency' => $currency,
            'accountingStandardLabel' => $standard['label'] ?? null,
            'encaissements' => $encaissements,
            'decaissements' => $decaissements,
            'totalEncaissements' => $totalEncaissements,
            'totalDecaissements' => $totalDecaissements,
            'solde' => $totalEncaissements - $totalDecaissements,
        ];
    }

    private function groupLines(Collection $transactions, string $groupKey, bool $hasStandard): Collection
    {
        return $transactions
            ->groupBy($groupKey)
            ->map(function ($group) use ($hasStandard) {
                $first = $group->first();

                return [
                    'account_code' => $hasStandard ? $first->account_code : null,
                    'account_label' => $hasStandard ? $first->account_label : (self::TYPE_LABELS[$first->type] ?? $first->type),
                    'total' => $group->sum('amount'),
                ];
            })
            ->values();
    }
}

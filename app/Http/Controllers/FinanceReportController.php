<?php

namespace App\Http\Controllers;

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

    public function show(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $month = $request->query('mois', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = $month.'-01';
        $end = date('Y-m-t', strtotime($start));

        $transactions = $orgUnit->financialTransactions()
            ->whereBetween('transaction_date', [$start, $end])
            ->orderBy('transaction_date')
            ->get();

        // Point 18 : avec une norme documentee, le detail se lit par
        // compte code (le plan de comptes du pays) ; sans norme, seule la
        // nature universelle du mouvement (type) reste disponible pour
        // regrouper les lignes - jamais un compte code invente.
        $standard = AccountingStandardResolver::forOrgUnit($orgUnit);
        $groupKey = $standard ? 'account_code' : 'type';

        $encaissements = $this->groupLines($transactions->where('nature', 'encaissement'), $groupKey, (bool) $standard);
        $decaissements = $this->groupLines($transactions->where('nature', 'decaissement'), $groupKey, (bool) $standard);

        $totalEncaissements = $encaissements->sum('total');
        $totalDecaissements = $decaissements->sum('total');

        return Inertia::render('Finances/Rapport', [
            'orgUnit' => $orgUnit,
            'month' => $month,
            'encaissements' => $encaissements,
            'decaissements' => $decaissements,
            'totalEncaissements' => $totalEncaissements,
            'totalDecaissements' => $totalDecaissements,
            'solde' => $totalEncaissements - $totalDecaissements,
            'currency' => AccountingStandardResolver::currencyFor($orgUnit),
            'accountingStandardLabel' => $standard['label'] ?? null,
        ]);
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

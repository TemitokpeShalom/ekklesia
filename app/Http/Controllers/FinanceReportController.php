<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceReportController extends Controller
{
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

        $encaissements = $transactions->where('nature', 'encaissement')
            ->groupBy('account_code')
            ->map(fn ($group) => [
                'account_code' => $group->first()->account_code,
                'account_label' => $group->first()->account_label,
                'total' => $group->sum('amount'),
            ])
            ->values();

        $decaissements = $transactions->where('nature', 'decaissement')
            ->groupBy('account_code')
            ->map(fn ($group) => [
                'account_code' => $group->first()->account_code,
                'account_label' => $group->first()->account_label,
                'total' => $group->sum('amount'),
            ])
            ->values();

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
        ]);
    }
}

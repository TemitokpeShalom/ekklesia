<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\OrgUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FinanceTransactionsController extends Controller
{
    private const INCOME_TYPES = ['dime', 'offrande', 'action_de_grace', 'don'];

    public function index(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $month = $this->resolveMonth($request);

        $transactions = $orgUnit->financialTransactions()
            ->whereBetween('transaction_date', [$month.'-01', date('Y-m-t', strtotime($month.'-01'))])
            ->orderByDesc('transaction_date')
            ->get();

        $totalEncaissements = $transactions->where('nature', 'encaissement')->sum('amount');
        $totalDecaissements = $transactions->where('nature', 'decaissement')->sum('amount');

        return Inertia::render('Finances/Index', [
            'orgUnit' => $orgUnit,
            'transactions' => $transactions,
            'month' => $month,
            'totals' => [
                'encaissements' => $totalEncaissements,
                'decaissements' => $totalDecaissements,
                'solde' => $totalEncaissements - $totalDecaissements,
            ],
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageFinances', $orgUnit);

        return Inertia::render('Finances/Create', [
            'orgUnit' => $orgUnit,
            'accounts' => $this->accountsForFrontend(),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);

        $data = $this->validateTransaction($request);

        $orgUnit->financialTransactions()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('finances.index', ['orgUnit' => $orgUnit->id]);
    }

    public function edit(OrgUnit $orgUnit, FinancialTransaction $transaction): Response
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($transaction->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Finances/Edit', [
            'orgUnit' => $orgUnit,
            'transaction' => $transaction,
            'accounts' => $this->accountsForFrontend(),
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, FinancialTransaction $transaction): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($transaction->org_unit_id === $orgUnit->id, 404);

        $data = $this->validateTransaction($request);

        $transaction->update($data);

        return redirect()->route('finances.index', ['orgUnit' => $orgUnit->id]);
    }

    public function destroy(OrgUnit $orgUnit, FinancialTransaction $transaction): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($transaction->org_unit_id === $orgUnit->id, 404);

        $transaction->delete();

        return redirect()->route('finances.index', ['orgUnit' => $orgUnit->id]);
    }

    private function resolveMonth(Request $request): string
    {
        $month = $request->query('mois', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : now()->format('Y-m');
    }

    private function validateTransaction(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(['dime', 'offrande', 'action_de_grace', 'don', 'depense'])],
            'account_code' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:8'],
            'transaction_date' => ['required', 'date'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $matched = collect($this->flatAccounts())->firstWhere('code', $data['account_code']);
        abort_unless($matched, 422, 'Compte comptable inconnu.');

        $data['nature'] = in_array($data['type'], self::INCOME_TYPES, true) ? 'encaissement' : 'decaissement';
        $data['account_label'] = $matched['label'];
        $data['currency'] = $data['currency'] ?: config('finance.default_currency');

        return $data;
    }

    private function accountsForFrontend(): array
    {
        return [
            'income' => config('finance.income_accounts'),
            'expense' => config('finance.expense_accounts'),
        ];
    }

    private function flatAccounts(): array
    {
        $income = collect(config('finance.income_accounts'))
            ->flatMap(fn ($group) => $group)
            ->values()
            ->all();

        return array_merge($income, config('finance.expense_accounts'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\OrgUnit;
use App\Services\AccountingStandardResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FinanceTransactionsController extends Controller
{
    private const INCOME_TYPES = ['dime', 'offrande', 'action_de_grace', 'don'];

    /**
     * Point 08 (mobile money) : simple mode de reglement declaratif, saisi
     * manuellement comme les autres modes, sans passerelle automatique.
     */
    public const PAYMENT_METHODS = ['especes', 'cheque', 'virement', 'mobile_money', 'autre'];

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

        $standard = AccountingStandardResolver::forOrgUnit($orgUnit);
        $canManage = $request->user()->can('manageFinances', $orgUnit);

        return Inertia::render('Finances/Index', [
            'orgUnit' => $orgUnit,
            'transactions' => $transactions,
            'month' => $month,
            'totals' => [
                'encaissements' => $totalEncaissements,
                'decaissements' => $totalDecaissements,
                'solde' => $totalEncaissements - $totalDecaissements,
            ],
            'currency' => AccountingStandardResolver::currencyFor($orgUnit),
            'accountingStandardLabel' => $standard['label'] ?? null,
            'paymentMethods' => self::PAYMENT_METHODS,
            'canManage' => $canManage,
            // Chantier "module Finances" (2026-09-12, retour du ministere) :
            // "un bouton... pour choisir la norme comptable" - seulement
            // propose a qui peut deja gerer les finances de cette entite.
            'accountingStandard' => $canManage ? [
                'currentCode' => $standard['code'] ?? null,
                'isOverride' => AccountingStandardResolver::overrideFor($orgUnit) !== null,
                'available' => collect(config('finance.standards'))
                    ->map(fn ($def, $code) => ['code' => $code, 'label' => $def['label']])
                    ->values(),
            ] : null,
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageFinances', $orgUnit);

        $standard = AccountingStandardResolver::forOrgUnit($orgUnit);

        return Inertia::render('Finances/Create', [
            'orgUnit' => $orgUnit,
            'accounts' => $standard ? $this->accountsForFrontend($standard) : null,
            'accountingStandardLabel' => $standard['label'] ?? null,
            'currency' => AccountingStandardResolver::currencyFor($orgUnit),
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);

        $data = $this->validateTransaction($request, $orgUnit);

        $orgUnit->financialTransactions()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
            'recorded_by' => $request->user()->id,
        ]);

        // Corrige le 2026-09-12 (retour du ministere, valable pour TOUS les
        // modules d'enregistrement) : confirmer clairement la reussite,
        // toujours en vert - jamais en rouge, reserve aux erreurs.
        return redirect()->route('finances.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Mouvement enregistré.');
    }

    public function edit(OrgUnit $orgUnit, FinancialTransaction $transaction): Response
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($transaction->org_unit_id === $orgUnit->id, 404);

        $standard = AccountingStandardResolver::forOrgUnit($orgUnit);

        return Inertia::render('Finances/Edit', [
            'orgUnit' => $orgUnit,
            'transaction' => $transaction,
            'accounts' => $standard ? $this->accountsForFrontend($standard) : null,
            'accountingStandardLabel' => $standard['label'] ?? null,
            'currency' => AccountingStandardResolver::currencyFor($orgUnit),
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, FinancialTransaction $transaction): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($transaction->org_unit_id === $orgUnit->id, 404);

        $data = $this->validateTransaction($request, $orgUnit);

        $transaction->update($data);

        return redirect()->route('finances.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Mouvement mis à jour.');
    }

    public function destroy(OrgUnit $orgUnit, FinancialTransaction $transaction): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($transaction->org_unit_id === $orgUnit->id, 404);

        $transaction->delete();

        return redirect()->route('finances.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Mouvement supprimé.');
    }

    private function resolveMonth(Request $request): string
    {
        $month = $request->query('mois', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : now()->format('Y-m');
    }

    /**
     * Point 18 : le champ "compte comptable" n'est exige que si le pays
     * de cet org_unit a une norme documentee (voir
     * AccountingStandardResolver) - sinon, le mouvement s'enregistre avec
     * sa seule nature universelle, jamais avec un code invente.
     */
    private function validateTransaction(Request $request, OrgUnit $orgUnit): array
    {
        $standard = AccountingStandardResolver::forOrgUnit($orgUnit);

        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(['dime', 'offrande', 'action_de_grace', 'don', 'depense'])],
            'account_code' => [$standard ? 'required' : 'nullable', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'max:8'],
            'payment_method' => ['nullable', Rule::in(self::PAYMENT_METHODS)],
            'transaction_date' => ['required', 'date'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $data['nature'] = in_array($data['type'], self::INCOME_TYPES, true) ? 'encaissement' : 'decaissement';

        if ($standard) {
            $matched = collect($this->flatAccounts($standard))->firstWhere('code', $data['account_code']);
            abort_unless($matched, 422, 'Compte comptable inconnu.');
            $data['account_label'] = $matched['label'];
        } else {
            $data['account_code'] = null;
            $data['account_label'] = null;
        }

        $data['currency'] = $data['currency'] ?: AccountingStandardResolver::currencyFor($orgUnit);

        return $data;
    }

    private function accountsForFrontend(array $standard): array
    {
        return [
            'income' => $standard['income_accounts'],
            'expense' => $standard['expense_accounts'],
        ];
    }

    private function flatAccounts(array $standard): array
    {
        $income = collect($standard['income_accounts'])
            ->flatMap(fn ($group) => $group)
            ->values()
            ->all();

        return array_merge($income, $standard['expense_accounts']);
    }
}

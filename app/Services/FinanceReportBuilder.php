<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\OrgUnit;
use App\Services\AccountingStandardResolver;
use Illuminate\Support\Collection;

/**
 * Extrait de FinanceReportController (chantier "module Documents",
 * 2026-09-12) : le meme calcul par devise sert desormais a deux endroits -
 * le rapport financier "en direct" (FinanceReportController::show) et son
 * archive imprimable une fois le mois valide (RapportsArchiveController) -
 * une seule version de la logique, jamais deux copies qui pourraient
 * diverger.
 */
class FinanceReportBuilder
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
     * Point 08 (rapports consolides multidevises) : vue consolidee = les
     * mouvements propres a ce noeud, plus ceux de tous ses descendants,
     * jamais uniquement ce seul noeud. Aucune conversion de change
     * n'existant dans l'application, le rapport ne totalise jamais deux
     * devises ensemble.
     *
     * Corrige le 2026-09-12 (retour du ministere, chantier "module
     * Finances") : chaque bloc devise porte desormais aussi un
     * "openingBalance" (solde de tout ce qui precede le mois, par simple
     * somme des mouvements anterieurs a $start - aucune colonne de solde
     * report stockee nulle part, jamais necessaire puisque tout est
     * recalculable depuis le journal) et un "ledger" chronologique,
     * mouvement par mouvement ("chaque mouvement doit apparaitre l'un par
     * l'un" - jamais regroupe par compte sur tout le mois comme
     * encaissements/decaissements ci-dessous, qui restent une vue par
     * nature complementaire, pas un remplacement).
     */
    public function build(OrgUnit $orgUnit, string $start, string $end): Collection
    {
        $orgUnitIds = OrgUnit::descendantsOf($orgUnit)->pluck('id');

        $transactions = FinancialTransaction::whereIn('org_unit_id', $orgUnitIds)
            ->whereBetween('transaction_date', [$start, $end])
            ->with('orgUnit')
            ->orderBy('transaction_date')
            ->orderBy('created_at')
            ->get();

        $openingBalances = FinancialTransaction::whereIn('org_unit_id', $orgUnitIds)
            ->where('transaction_date', '<', $start)
            ->selectRaw("currency, sum(case when nature = 'encaissement' then amount else -amount end) as solde")
            ->groupBy('currency')
            ->pluck('solde', 'currency');

        $currencies = $transactions->pluck('currency')->concat($openingBalances->keys())->unique();

        return $currencies
            ->map(fn ($currency) => $this->buildDeviseBlock(
                $currency,
                $transactions->where('currency', $currency),
                (float) ($openingBalances->get($currency) ?? 0)
            ))
            ->sortBy('currency')
            ->values();
    }

    /**
     * Le detail par compte comptable n'a de sens que si TOUTES les lignes
     * de cette devise viennent du meme plan de comptes (account_code deja
     * renseigne a la saisie) - des que la devise melange des mouvements
     * avec et sans compte comptable, on retombe sur la seule nature
     * universelle (type), jamais un compte invente ou mélangé entre deux
     * normes.
     */
    private function buildDeviseBlock(string $currency, Collection $group, float $openingBalance): array
    {
        $hasStandard = $group->isNotEmpty() && $group->every(fn ($t) => $t->account_code !== null);
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
            'openingBalance' => $openingBalance,
            'closingBalance' => $openingBalance + $totalEncaissements - $totalDecaissements,
            'ledger' => $this->buildLedger($group),
        ];
    }

    /**
     * Journal chronologique, mouvement par mouvement, jamais regroupe -
     * "chaque ligne, chaque jour, chaque mouvement doit apparaitre l'un
     * par l'un" (retour du ministere, 2026-09-12). Le nom de l'entite
     * n'est utile que sur une vue consolidee (plusieurs eglises) : reste
     * present mais que l'ecran l'affiche ou non depend de la vue.
     */
    private function buildLedger(Collection $transactions): array
    {
        // Deja trie chronologiquement par la requete d'origine
        // (orderBy transaction_date, created_at) - where() preserve cet
        // ordre, inutile de retrier ici.
        return $transactions
            ->values()
            ->map(fn ($t) => [
                'id' => $t->id,
                'date' => $t->transaction_date instanceof \DateTimeInterface ? $t->transaction_date->format('Y-m-d') : $t->transaction_date,
                'nature' => $t->nature,
                'type' => $t->type,
                'type_label' => self::TYPE_LABELS[$t->type] ?? $t->type,
                'account_code' => $t->account_code,
                'account_label' => $t->account_label,
                'counterparty' => $t->counterparty,
                'org_unit_name' => $t->orgUnit?->name,
                'amount' => (float) $t->amount,
            ])
            ->all();
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

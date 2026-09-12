<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetInventoryValidation;
use App\Models\OrgUnit;
use App\Services\AccountingStandardResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Registre des biens, immobiliers et mobiliers (point 19). Rattache au
 * meme bloc roadmap que les Finances (point 18) : meme droit de gestion
 * (le tresorier), et la fiche d'inventaire consolidee suit la meme regle
 * « activite propre » que les effectifs (point 06) et les finances.
 */
class AssetsController extends Controller
{
    private const CATEGORIES = ['immobilier', 'mobilier'];

    private const PROVENANCES = ['don', 'achat_caisse', 'achat_offrande', 'subvention', 'legs', 'construction'];

    private const CONDITIONS = ['fonctionnel', 'a_surveiller', 'hors_service'];

    public function index(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $categorie = $request->query('categorie');
        $categorie = in_array($categorie, self::CATEGORIES, true) ? $categorie : null;

        $assets = $orgUnit->assets()
            ->when($categorie, fn ($q) => $q->where('category', $categorie))
            ->orderByDesc('acquisition_date')
            ->get();

        return Inertia::render('Inventaire/Index', [
            'orgUnit' => $orgUnit,
            'assets' => $assets,
            'categorie' => $categorie,
            'totaux' => [
                'immobilier' => (float) $orgUnit->assets()->where('category', 'immobilier')->sum('acquisition_value'),
                'mobilier' => (float) $orgUnit->assets()->where('category', 'mobilier')->sum('acquisition_value'),
            ],
            // Point 18 : meme devise par pays que les mouvements financiers, jamais un "FCFA" fige.
            'currency' => AccountingStandardResolver::currencyFor($orgUnit),
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageFinances', $orgUnit);

        return Inertia::render('Inventaire/Create', [
            'orgUnit' => $orgUnit,
            'depenses' => $this->depensesDisponibles($orgUnit),
            'currency' => AccountingStandardResolver::currencyFor($orgUnit),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);

        $data = $this->validateAsset($request, $orgUnit);

        $orgUnit->assets()->create($data + [
            'ministry_id' => $orgUnit->ministry_id,
            'code' => $this->nextCode($orgUnit, $data['category']),
        ]);

        // Corrige le 2026-09-12 (retour du ministere, valable pour TOUS les
        // modules d'enregistrement) : confirmer clairement la reussite,
        // toujours en vert - jamais en rouge, reserve aux erreurs.
        return redirect()->route('inventaire.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Bien enregistré.');
    }

    public function edit(OrgUnit $orgUnit, Asset $asset): Response
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($asset->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Inventaire/Edit', [
            'orgUnit' => $orgUnit,
            'asset' => $asset,
            'depenses' => $this->depensesDisponibles($orgUnit),
            'currency' => AccountingStandardResolver::currencyFor($orgUnit),
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, Asset $asset): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($asset->org_unit_id === $orgUnit->id, 404);

        $data = $this->validateAsset($request, $orgUnit);

        $asset->update($data);

        return redirect()->route('inventaire.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Bien mis à jour.');
    }

    public function destroy(OrgUnit $orgUnit, Asset $asset): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($asset->org_unit_id === $orgUnit->id, 404);

        // Suppression douce (point 19) : le code d'identification n'est
        // jamais reutilise, meme apres le retrait d'un bien.
        $asset->delete();

        return redirect()->route('inventaire.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Bien retiré.');
    }

    public function rapport(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $date = $request->query('date', now()->format('Y-m-d'));
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : now()->format('Y-m-d');
        $year = (int) date('Y', strtotime($date));

        $parCategorie = $this->consolidatedAssets($orgUnit, $date);

        $validation = AssetInventoryValidation::where('org_unit_id', $orgUnit->id)
            ->where('year', $year)
            ->with('validator:id,name')
            ->first();

        return Inertia::render('Inventaire/Rapport', [
            'orgUnit' => $orgUnit,
            'date' => $date,
            'year' => $year,
            'parCategorie' => $parCategorie,
            // Devise du pays du noeud consulte - une consolidation qui remonte
            // au-dela d'un seul pays melange donc des devises differentes dans
            // ce total, comme deja le cas avant le point 18 (aucune conversion
            // de change n'existe dans l'application).
            'currency' => AccountingStandardResolver::currencyFor($orgUnit),
            // En-tete officiel + bloc "position" (retour du ministere,
            // 2026-09-12 : "un peu comme sur les rapports [...] au niveau
            // qui suit les informations sur l'eglise") - meme convention que
            // Finances/Activites.
            'ministry' => $orgUnit->ministry->letterhead(),
            'ancestry' => $orgUnit->ancestryChain(),
            'pastorName' => $orgUnit->pastorName(),
            'validation' => $validation ? [
                'validated_at' => $validation->validated_at,
                'validator_name' => $validation->validator?->name,
            ] : null,
            'canManage' => $request->user()->can('manageFinances', $orgUnit),
        ]);
    }

    /**
     * Chantier "module Inventaire" (2026-09-12, retour du ministere) :
     * "c'est un bouton qui manque et c'est un document qui manque" - meme
     * principe que FinanceReportController::validateReport : "valider" ne
     * fait qu'attester que l'annee a ete verifiee et peut etre
     * archivee/imprimee depuis Documents > Rapports ; cela ne bloque pas
     * l'enregistrement d'un bien pour cette annee (memes limites deja
     * acceptees pour les Finances - verrouiller la saisie serait un
     * chantier a part, plus lourd).
     */
    public function validateReport(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);

        $year = (int) $request->input('year', now()->format('Y'));

        AssetInventoryValidation::updateOrCreate(
            ['org_unit_id' => $orgUnit->id, 'year' => $year],
            ['ministry_id' => $orgUnit->ministry_id, 'validated_at' => now(), 'validated_by' => $request->user()->id]
        );

        return redirect()->route('inventaire.rapport', ['orgUnit' => $orgUnit->id, 'date' => "{$year}-12-31"])
            ->with('success', "Fiche d'inventaire {$year} validée et archivée.");
    }

    public function unlock(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);

        $year = (int) $request->input('year', now()->format('Y'));

        AssetInventoryValidation::where('org_unit_id', $orgUnit->id)->where('year', $year)->delete();

        return redirect()->route('inventaire.rapport', ['orgUnit' => $orgUnit->id, 'date' => "{$year}-12-31"])
            ->with('success', "Fiche d'inventaire {$year} déverrouillée.");
    }

    /**
     * Extrait de rapport() (2026-09-12) pour etre reutilise tel quel par
     * l'archive imprimable (RapportsArchiveController::inventaire) une
     * fois l'annee validee - mêmes chiffres partout, jamais recalcules
     * differemment d'un endroit a l'autre.
     */
    public function consolidatedAssets(OrgUnit $orgUnit, string $date)
    {
        // Fiche consolidee = biens propres + biens de tous les descendants
        // (point 12, "activite propre"), a la date choisie.
        $orgUnitIds = OrgUnit::descendantsOf($orgUnit)->pluck('id');

        $assets = Asset::whereIn('org_unit_id', $orgUnitIds)
            ->where(function ($q) use ($date) {
                $q->whereNull('acquisition_date')->orWhere('acquisition_date', '<=', $date);
            })
            ->with('orgUnit:id,name,level_label')
            ->orderBy('category')
            ->orderBy('code')
            ->get();

        return $assets->groupBy('category')->map(fn ($group) => [
            'items' => $group->values(),
            'total' => (float) $group->sum('acquisition_value'),
        ]);
    }

    private function validateAsset(Request $request, OrgUnit $orgUnit): array
    {
        $data = $request->validate([
            'category' => ['required', 'string', Rule::in(self::CATEGORIES)],
            'label' => ['required', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'acquisition_date' => ['nullable', 'date'],
            'acquisition_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'provenance' => ['required', 'string', Rule::in(self::PROVENANCES)],
            'financial_transaction_id' => ['nullable', 'uuid'],
            'condition' => ['required', 'string', Rule::in(self::CONDITIONS)],
            'observation' => ['nullable', 'string'],
        ]);

        if (! empty($data['financial_transaction_id'])) {
            $exists = $orgUnit->financialTransactions()
                ->where('id', $data['financial_transaction_id'])
                ->where('type', 'depense')
                ->exists();
            abort_unless($exists, 422, 'Dépense liée introuvable pour ce niveau.');
        }

        $data['quantity'] = $data['quantity'] ?: 1;
        $data['currency'] = $data['currency'] ?: AccountingStandardResolver::currencyFor($orgUnit);

        return $data;
    }

    private function nextCode(OrgUnit $orgUnit, string $category): string
    {
        $prefix = $category === 'immobilier' ? 'BAT' : 'MOB';

        // Jamais reutilise, meme apres retrait d'un bien (point 19) : on
        // regarde aussi les biens retires (withTrashed) pour ne jamais
        // reattribuer un numero deja pris.
        $lastNumber = Asset::withTrashed()
            ->where('ministry_id', $orgUnit->ministry_id)
            ->where('category', $category)
            ->selectRaw("MAX(substring(code from '[0-9]+$')::int) as last_number")
            ->value('last_number');

        return sprintf('%s-%06d', $prefix, ((int) $lastNumber) + 1);
    }

    private function depensesDisponibles(OrgUnit $orgUnit): array
    {
        return $orgUnit->financialTransactions()
            ->where('type', 'depense')
            ->orderByDesc('transaction_date')
            ->limit(50)
            ->get(['id', 'transaction_date', 'account_label', 'amount', 'currency'])
            ->all();
    }
}

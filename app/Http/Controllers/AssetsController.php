<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\OrgUnit;
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
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageFinances', $orgUnit);

        return Inertia::render('Inventaire/Create', [
            'orgUnit' => $orgUnit,
            'depenses' => $this->depensesDisponibles($orgUnit),
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

        return redirect()->route('inventaire.index', ['orgUnit' => $orgUnit->id]);
    }

    public function edit(OrgUnit $orgUnit, Asset $asset): Response
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($asset->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Inventaire/Edit', [
            'orgUnit' => $orgUnit,
            'asset' => $asset,
            'depenses' => $this->depensesDisponibles($orgUnit),
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, Asset $asset): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($asset->org_unit_id === $orgUnit->id, 404);

        $data = $this->validateAsset($request, $orgUnit);

        $asset->update($data);

        return redirect()->route('inventaire.index', ['orgUnit' => $orgUnit->id]);
    }

    public function destroy(OrgUnit $orgUnit, Asset $asset): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);
        abort_unless($asset->org_unit_id === $orgUnit->id, 404);

        // Suppression douce (point 19) : le code d'identification n'est
        // jamais reutilise, meme apres le retrait d'un bien.
        $asset->delete();

        return redirect()->route('inventaire.index', ['orgUnit' => $orgUnit->id]);
    }

    public function rapport(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $date = $request->query('date', now()->format('Y-m-d'));
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : now()->format('Y-m-d');

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

        $parCategorie = $assets->groupBy('category')->map(fn ($group) => [
            'items' => $group->values(),
            'total' => (float) $group->sum('acquisition_value'),
        ]);

        return Inertia::render('Inventaire/Rapport', [
            'orgUnit' => $orgUnit,
            'date' => $date,
            'parCategorie' => $parCategorie,
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
        $data['currency'] = $data['currency'] ?: config('finance.default_currency');

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

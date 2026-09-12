<?php

namespace App\Http\Controllers;

use App\Models\FinancialReportValidation;
use App\Models\OrgUnit;
use App\Services\FinanceReportBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceReportController extends Controller
{
    public function __construct(private FinanceReportBuilder $reportBuilder)
    {
    }

    /**
     * Point 08 (rapports consolidés multidevises) : vue consolidée = les
     * mouvements propres a ce noeud, plus ceux de tous ses descendants
     * (meme regle « activite propre » que les effectifs/l'inventaire),
     * jamais uniquement ce seul noeud. Comme le ministere pilote est reparti
     * sur plusieurs pays a devises differentes, et qu'aucune conversion de
     * change n'existe dans l'application (meme principe deja applique a
     * l'inventaire, point 19), le rapport ne totalise jamais deux devises
     * ensemble : chaque devise rencontree obtient son propre bloc, avec son
     * propre detail et son propre solde. Le calcul lui-meme vit desormais
     * dans FinanceReportBuilder (chantier "module Documents", 2026-09-12),
     * partage avec l'archive imprimable une fois le mois valide.
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

        $devises = $this->reportBuilder->build($orgUnit, $start, $end);

        $validation = FinancialReportValidation::where('org_unit_id', $orgUnit->id)
            ->where('period', $start)
            ->with('validator:id,name')
            ->first();

        return Inertia::render('Finances/Rapport', [
            'orgUnit' => $orgUnit,
            'month' => $month,
            // En-tete officiel du ministere (2026-09-11, voir
            // Ministry::letterhead()) - ce rapport est un document, il porte
            // desormais l'identite du ministere comme un en-tete de courrier.
            'ministry' => $orgUnit->ministry->letterhead(),
            // Bloc "position" (retour du ministere, 2026-09-12 : "la
            // position de l'eglise concernee... et le nom du pasteur") -
            // deja present sur l'archive imprimable, ajoute ici pour que
            // le rapport "vivant" (avant validation) le montre aussi.
            'ancestry' => $orgUnit->ancestryChain(),
            'pastorName' => $orgUnit->pastorName(),
            'devises' => $devises,
            'validation' => $validation ? [
                'validated_at' => $validation->validated_at,
                'validator_name' => $validation->validator?->name,
            ] : null,
            'canManage' => $request->user()->can('manageFinances', $orgUnit),
        ]);
    }

    /**
     * Chantier "module Documents" (2026-09-12) : contrairement au rapport
     * d'activites, il n'y a pas de ligne a verrouiller ici (voir
     * FinancialReportValidation) - "valider" ne fait qu'attester que ce
     * mois a ete verifie et peut etre archive/imprime depuis Documents >
     * Rapports ; cela ne bloque pas la saisie d'un mouvement pour ce mois
     * (verrouiller la saisie elle-meme serait un chantier a part, plus
     * lourd - voir LISEZ-MOI de cette livraison).
     */
    public function validateReport(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);

        $month = $request->input('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        FinancialReportValidation::updateOrCreate(
            ['org_unit_id' => $orgUnit->id, 'period' => $month.'-01'],
            ['ministry_id' => $orgUnit->ministry_id, 'validated_at' => now(), 'validated_by' => $request->user()->id]
        );

        return redirect()->route('finances.rapport', ['orgUnit' => $orgUnit->id, 'mois' => $month])
            ->with('success', 'Rapport financier validé et archivé.');
    }

    public function unlock(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageFinances', $orgUnit);

        $month = $request->input('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        FinancialReportValidation::where('org_unit_id', $orgUnit->id)->where('period', $month.'-01')->delete();

        return redirect()->route('finances.rapport', ['orgUnit' => $orgUnit->id, 'mois' => $month])
            ->with('success', 'Rapport financier déverrouillé.');
    }
}

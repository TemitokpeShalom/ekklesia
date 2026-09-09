<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Services\AttachmentCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Controleur volontairement mince (point 10) : il valide l'entree, appelle
 * le service, renvoie une reponse - la logique reste dans
 * AttachmentCodeService, reutilisable demain par une API mobile.
 */
class AttachmentCodeController extends Controller
{
    public function __construct(private AttachmentCodeService $attachmentCodes)
    {
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('issueAttachmentCode', $orgUnit);

        return Inertia::render('OrgUnits/IssueAttachmentCode', ['orgUnit' => $orgUnit]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('issueAttachmentCode', $orgUnit);

        $validated = $request->validate([
            'target_level_rank' => ['required', 'integer', 'min:0', 'max:6'],
            'valid_for_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ]);

        [$attachmentCode, $plainCode] = $this->attachmentCodes->issue(
            $orgUnit,
            $validated['target_level_rank'],
            $request->user(),
            $validated['valid_for_hours'] ?? 72,
        );

        return back()->with('plain_code', $plainCode)->with('attachment_code_id', $attachmentCode->id);
    }

    /**
     * Page publique (point 03) : la seule ou saisir un code de
     * rattachement. Accessible sans compte - c'est le cas normal d'une
     * eglise reellement nouvelle - donc hors du groupe auth/tenant.context
     * de routes/web.php. Si un code est deja connu (lien transmis avec le
     * code en parametre), on l'affiche a l'avance pour eviter une saisie
     * manuelle source d'erreur, mais sans jamais reveler a quel niveau il
     * ouvre droit avant validation (le code seul fait foi, point 03).
     */
    public function redeemShow(Request $request): Response
    {
        return Inertia::render('OrgUnits/RedeemAttachmentCode', [
            'authenticated' => $request->user() !== null,
            'prefillCode' => $request->query('code'),
        ]);
    }

    public function redeem(Request $request): RedirectResponse
    {
        $rules = [
            'code' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'code_short' => ['nullable', 'string', 'max:255'],
            'level_label' => ['required', 'string', 'max:255'],
        ];

        $authenticatedUser = $request->user();

        if (! $authenticatedUser) {
            // Personne pas encore connue d'Ekklesia (nouvelle eglise) : son
            // compte est cree dans le meme geste que le rattachement, comme
            // pour une invitation (point 11) - jamais un compte partage.
            $rules += [
                'account_name' => ['required', 'string', 'max:255'],
                'account_email' => ['required', 'email', 'max:255'],
                'account_phone' => ['nullable', 'string', 'max:255'],
                'account_password' => ['required', 'string', 'min:8', 'confirmed'],
            ];
        }

        $validated = $request->validate($rules);

        $newAccount = $authenticatedUser ? null : [
            'name' => $validated['account_name'],
            'email' => $validated['account_email'],
            'phone' => $validated['account_phone'] ?? null,
            'password' => Hash::make($validated['account_password']),
        ];

        try {
            [$newUnit, $usedBy] = $this->attachmentCodes->consume($validated['code'], [
                'name' => $validated['name'],
                'code' => $validated['code_short'] ?: $validated['name'],
                'level_label' => $validated['level_label'],
            ], $authenticatedUser, $newAccount);
        } catch (RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }

        if (! $authenticatedUser) {
            auth()->login($usedBy);
            $request->session()->regenerate();
        }

        // Complement du point 04, meme raison que LoginController@store :
        // sans ceci, la policy RLS par ministere de la requete suivante
        // (le tableau de bord vers lequel on redirige juste en dessous)
        // ne laisse rien passer et produit une fausse erreur 404/403.
        $request->session()->put('current_ministry_id', $newUnit->ministry_id);

        return redirect()
            ->route('dashboard', ['orgUnit' => $newUnit->id])
            ->with('success', "« {$newUnit->name} » a été créée et rattachée automatiquement.");
    }
}

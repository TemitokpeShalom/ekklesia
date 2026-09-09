<?php

namespace App\Http\Controllers;

use App\Services\MinistryRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Creation libre-service d'un nouveau ministere (2026-09-09) - second
 * parcours de la page d'accueil (voir WelcomeController), a cote de "se
 * connecter à mon espace de travail". Controleur volontairement mince
 * (meme principe que AttachmentCodeController et InvitationController) :
 * la logique vit dans MinistryRegistrationService.
 *
 * L'email du compte fondateur doit etre inedit (contrainte 'unique' plus
 * bas) - contrairement a AttachmentCodeController::redeem() et
 * InvitationController::acceptStore(), qui rattachent silencieusement un
 * email deja connu au compte existant. Ce comportement n'est pas repris
 * ici volontairement : un visiteur anonyme qui saisirait l'email de
 * quelqu'un d'autre sur CE formulaire n'a aucun moyen de prouver qu'il en
 * est le titulaire (pas de jeton, pas de code), contrairement a une
 * invitation ou un code de rattachement qui, eux, prouvent un mandat.
 */
class MinistryRegistrationController extends Controller
{
    public function __construct(private MinistryRegistrationService $ministryRegistration)
    {
    }

    public function create(): Response
    {
        return Inertia::render('Ministries/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'account_phone' => ['nullable', 'string', 'max:255'],
            'account_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        [$ministry, $root, $user] = $this->ministryRegistration->register(
            ['name' => $validated['name']],
            [
                'name' => $validated['account_name'],
                'email' => $validated['account_email'],
                'phone' => $validated['account_phone'] ?? null,
                'password' => $validated['account_password'],
            ]
        );

        auth()->login($user);
        $request->session()->regenerate();

        // Complement du point 04, meme raison que LoginController@store et
        // AttachmentCodeController@redeem : sans ceci, la policy RLS par
        // ministere de la requete suivante (le tableau de bord vers lequel
        // on redirige juste en dessous) ne laisse rien passer.
        $request->session()->put('current_ministry_id', $ministry->id);

        return redirect()
            ->route('dashboard', ['orgUnit' => $root->id])
            ->with('success', "« {$ministry->name} » a été créé. Bienvenue sur Ekklesia !");
    }
}

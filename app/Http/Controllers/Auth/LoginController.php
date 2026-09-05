<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        $request->session()->regenerate();

        // Complement du point 04 : a cet instant precis, le ministere
        // courant n'est pas encore connu (c'est justement ce qu'on cherche
        // a determiner ci-dessous), donc la policy RLS par ministere ne
        // peut rien laisser passer. On fixe ici l'identite de
        // l'utilisateur pour que la policy affectations_self_access
        // autorise la lecture de ses propres affectations.
        DB::statement('SELECT set_config(?, ?, true)', [
            'app.current_user_id',
            (string) $request->user()->id,
        ]);

        $firstAffectation = $request->user()->activeAffectations()->with('orgUnit')->first();

        if (! $firstAffectation) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => "Ce compte n'a aucune affectation active. Contactez votre responsable.",
            ]);
        }

        // Prepare le contexte multi-tenant (point 04/09) : ministere de
        // la premiere affectation active, ajustable par le
        // context-switcher pour une personne multi-affectee.
        $request->session()->put('current_ministry_id', $firstAffectation->ministry_id);

        return redirect()->route('dashboard', ['orgUnit' => $firstAffectation->org_unit_id]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

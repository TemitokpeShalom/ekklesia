<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Creation du compte d'un membre de l'equipe technique Oikonema
 * (2026-09-13). Ces comptes n'appartiennent a AUCUN ministere (aucune
 * Affectation) - retour du ministere du 13/09 : "je n'ai pas encore
 * enregistré ça sur la plateforme comme appartenant à un ministère".
 * Aucun des parcours de creation de compte existants (inscription d'un
 * ministere, invitation, code de rattachement) ne convient : tous les
 * trois creent un compte ET une Affectation dans le meme geste. Reserve
 * aux adresses deja listees dans TECHNICAL_STAFF_EMAILS (.env, voir
 * config/oikonema.php) : ce reglage sur le serveur fait office
 * d'autorisation prealable, la personne ne fait ici que poser un mot de
 * passe sur une adresse deja approuvee - jamais une inscription ouverte a
 * n'importe qui.
 *
 * Complement indispensable, fait dans le meme geste : LoginController et
 * HomeController rejetaient jusqu'ici tout compte sans affectation active
 * (pense pour un compte de ministere mal configure) - corriges pour
 * laisser passer un compte technique vers /technique au lieu de le
 * rejeter comme si c'etait une erreur.
 */
class TechnicalAccountController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Technique/CreerCompte');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $autorisees = array_map('strtolower', config('oikonema.technical_staff_emails', []));

        if (! in_array(strtolower($data['email']), $autorisees, true)) {
            throw ValidationException::withMessages([
                'email' => "Cette adresse n'a pas été autorisée pour l'équipe technique sur ce serveur. Demande à quelqu'un ayant accès au serveur de l'ajouter d'abord (TECHNICAL_STAFF_EMAILS dans le .env), puis réessaie.",
            ]);
        }

        if (User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Un compte existe déjà avec cette adresse : utilise plutôt la page de connexion.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => 'active',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('technique.index')->with('success', 'Compte technique créé.');
    }
}

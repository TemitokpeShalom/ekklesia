<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mon profil" (v4 "Constellation", 2026-09-10) : demande explicite du
 * ministere lors de la refonte visuelle - "il y a des pages ou on a le
 * profil de l'utilisateur... comment un bon profil" - jusqu'ici aucun
 * ecran ne montrait le compte de la personne connectee independamment
 * d'un ministere precis (voir AppLayout.vue, menu "profil").
 *
 * Non lie a un OrgUnit : une personne modifie SON PROPRE compte (nom,
 * telephone, mot de passe), quel que soit le ministere ou elle se trouve
 * au moment ou elle ouvre cet ecran - meme principe que /aide
 * (HelpController), aucune policy necessaire au-dela de "etre connecte".
 *
 * L'email n'est volontairement PAS modifiable ici : c'est l'identifiant de
 * connexion (voir LoginController) et le changer soulevait des questions
 * hors du perimetre de cette demande (verification, doublons) - laisse en
 * lecture seule pour l'instant, avec une note invitant a contacter le
 * support si besoin reel.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'profileUser' => $user->only(['id', 'name', 'email', 'phone']),
            'affectations' => $user->activeAffectations()
                ->with(['role:id,label', 'orgUnit:id,name,level_label'])
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'role' => $a->role->label,
                    'org_unit' => $a->orgUnit->name,
                    'level_label' => $a->orgUnit->level_label,
                ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($data);

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Le cast 'password' => 'hashed' du modele User se charge lui-meme
        // du hachage a l'affectation : passer un mot de passe deja hache
        // ici le hacherait une seconde fois (voir User::casts()).
        $user->update(['password' => $data['password']]);

        return back()->with('success', 'Mot de passe modifié.');
    }
}

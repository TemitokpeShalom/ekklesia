<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                // Espace technique Oikonema (2026-09-13) : is_technical_staff
                // ajoute ici pour que AppLayout.vue puisse afficher le lien
                // "Espace technique" dans le menu profil sans requete
                // supplementaire, seulement pour les comptes concernes (voir
                // User::isTechnicalStaff).
                'user' => $request->user() ? [
                    ...$request->user()->only(['id', 'name', 'email']),
                    'is_technical_staff' => $request->user()->isTechnicalStaff(),
                ] : null,
            ],
            // Messages ephemeres (code de rattachement emis, lien
            // d'invitation genere...) - lus une seule fois cote Vue.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'plain_code' => fn () => $request->session()->get('plain_code'),
                'invitation_link' => fn () => $request->session()->get('invitation_link'),
            ],
        ];
    }
}

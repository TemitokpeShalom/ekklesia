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
                'user' => $request->user()?->only(['id', 'name', 'email']),
            ],
            // Messages ephemeres (code de rattachement emis, lien
            // d'invitation genere...) - lus une seule fois cote Vue.
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'plain_code' => fn () => $request->session()->get('plain_code'),
                'invitation_link' => fn () => $request->session()->get('invitation_link'),
            ],
        ];
    }
}

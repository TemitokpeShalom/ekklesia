<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Pages legales publiques (conditions d'utilisation, confidentialite).
 * Accessibles sans connexion, depuis l'accueil et l'inscription.
 */
class LegalController extends Controller
{
    public function terms(): Response
    {
        return Inertia::render('Legal/Conditions', $this->shared());
    }

    public function privacy(): Response
    {
        return Inertia::render('Legal/Confidentialite', $this->shared());
    }

    private function shared(): array
    {
        return [
            'version' => config('oikonema.terms_version'),
            'contactEmail' => config('oikonema.contact_email'),
        ];
    }
}

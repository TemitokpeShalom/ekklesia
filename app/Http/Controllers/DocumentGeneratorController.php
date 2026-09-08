<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\OrgUnit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Generateur de documents (reliquat du point 08) : hub qui regroupe les
 * trois gabarits imprimables prevus a l'architecture. Le trombinoscope
 * existe deja (TrombinoscopeController, route inchangee) ; ce controleur
 * ajoute l'affiche et le calendrier annuel, construits sur la meme
 * traversee d'arbre (path <@ ce noeud, membres actifs uniquement).
 *
 * Le calendrier s'appuie sur birth_date (deja present sur Member) plutot
 * que sur un nouveau modele "evenements" : l'architecture ne demande pas
 * explicitement un calendrier d'evenements, seulement un support imprime
 * par mois, et les dates de naissance suffisent a le remplir utilement
 * des aujourd'hui - un vrai modele d'evenements pourra venir plus tard
 * sans remettre en cause ce gabarit.
 */
class DocumentGeneratorController extends Controller
{
    private const MAX_MEMBERS = 500;

    private const TEMPLATES = ['affiche', 'calendrier'];

    private const MOIS = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        return Inertia::render('Documents/Generateur', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
        ]);
    }

    public function show(Request $request, OrgUnit $orgUnit, string $template): Response
    {
        $this->authorize('view', $orgUnit);
        abort_unless(in_array($template, self::TEMPLATES, true), 404);

        $members = Member::whereHas('orgUnit', function ($q) use ($orgUnit) {
            $q->whereRaw('org_units.path <@ ?::ltree', [$orgUnit->path]);
        })
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::MAX_MEMBERS)
            ->get(['id', 'first_name', 'last_name', 'title', 'phone', 'birth_date', 'org_unit_id']);

        $payload = [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'ministry' => $orgUnit->ministry->only(['id', 'name']),
            'template' => $template,
            'year' => now()->year,
        ];

        if ($template === 'affiche') {
            $payload['leaders'] = $members
                ->filter(fn ($m) => filled($m->title))
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'name' => "{$m->first_name} {$m->last_name}",
                ])
                ->values();
            $payload['memberCount'] = $members->count();
        }

        if ($template === 'calendrier') {
            $payload['months'] = collect(self::MOIS)->map(function ($label, $num) use ($members) {
                return [
                    'number' => $num,
                    'label' => $label,
                    'members' => $members
                        ->filter(fn ($m) => $m->birth_date && (int) $m->birth_date->format('n') === $num)
                        ->sortBy(fn ($m) => (int) $m->birth_date->format('j'))
                        ->map(fn ($m) => [
                            'id' => $m->id,
                            'name' => trim("{$m->title} {$m->first_name} {$m->last_name}"),
                            'day' => (int) $m->birth_date->format('j'),
                        ])
                        ->values(),
                ];
            })->values();
        }

        return Inertia::render('Documents/Apercu', $payload);
    }
}

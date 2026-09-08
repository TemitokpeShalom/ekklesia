<?php

namespace App\Http\Controllers;

use App\Models\Ministry;
use App\Models\OrgUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Titres honorifiques configurables (reliquat du point 08) : la liste
 * utilisee par le champ "titre" du formulaire membre. Reglage unique par
 * ministere (stocke dans ministries.settings), donc ecran reserve a la
 * racine de l'arbre (rang 0) - le meme droit de gestion des personnes
 * (can_manage_users) que la gouvernance des acces, verifie ici via la
 * policy manageMembers deja utilisee par MembersController.
 */
class HonorificTitlesController extends Controller
{
    public function edit(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($orgUnit->level_rank === OrgUnit::RANK_MINISTERE, 404);

        return Inertia::render('Settings/HonorificTitles', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'titles' => $orgUnit->ministry->honorificTitles(),
            'defaults' => Ministry::DEFAULT_HONORIFIC_TITLES,
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($orgUnit->level_rank === OrgUnit::RANK_MINISTERE, 404);

        $data = $request->validate([
            'titles' => ['required', 'array', 'min:1'],
            'titles.*' => ['required', 'string', 'max:50'],
        ]);

        $titles = array_values(array_unique(array_filter(array_map('trim', $data['titles']))));

        $ministry = $orgUnit->ministry;
        $ministry->update([
            'settings' => [...($ministry->settings ?? []), 'honorific_titles' => $titles],
        ]);

        return redirect()->route('honorific-titles.edit', ['orgUnit' => $orgUnit->id]);
    }
}

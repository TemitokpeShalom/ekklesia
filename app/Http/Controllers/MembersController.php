<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\OrgUnit;
use App\Models\Role;
use App\Services\DiscipleshipStageRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion des membres (fideles) rattaches a une unite d'organisation.
 * Isolation stricte par ministry_id (point 04) : chaque membre appartient
 * au meme ministere que l'unite d'organisation a laquelle il est rattache.
 */
class MembersController extends Controller
{
    // Photos de profil (+ conjoint), point 08 : meme stockage local (disque
    // "public") que les pieces jointes des annonces (point 07).
    private const MAX_PHOTO_KB = 5120;

    public function index(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $search = trim((string) $request->query('q', ''));

        return Inertia::render('Members/Index', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'members' => $orgUnit->members()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'phone', 'email', 'status', 'joined_at', 'photo_path']),
            'search' => $search,
            'searchResults' => $search !== '' ? $this->searchMembers($orgUnit, $search) : [],
        ]);
    }

    /**
     * Corrige le 2026-09-12 (retour du ministere : "il n'y a pas de champ
     * de recherche... pour voir si telle personne est reellement membre de
     * notre ministere et sur quelle eglise locale") : jusqu'ici, ce module
     * ne montrait QUE les membres du noeud courant, sans aucun moyen de
     * retrouver quelqu'un ailleurs dans le ministere. Recherche etendue a
     * ce noeud ET tous ses descendants (meme perimetre "consolide" que la
     * Bibliotheque et les rapports finance/activites - jamais au-dela de ce
     * que l'utilisateur est autorise a voir) : depuis la racine du
     * ministere, cela couvre bien "toute la base du ministere" comme
     * demande.
     *
     * Chaque resultat rappelle l'eglise locale du membre ET son pasteur
     * (nom + telephone), pour qu'on puisse verifier "sur quelle eglise il
     * est" et joindre facilement le responsable concerne - en plus des
     * coordonnees propres du membre.
     *
     * @return array<int, array{member: array, org_unit: array, pastor: ?array}>
     */
    private function searchMembers(OrgUnit $orgUnit, string $search): array
    {
        $like = '%'.$search.'%';

        $matches = Member::whereHas('orgUnit', function ($q) use ($orgUnit) {
            $q->whereRaw('org_units.path <@ ?::ltree', [$orgUnit->path]);
        })
            ->where(function ($q) use ($like) {
                $q->where('first_name', 'ilike', $like)
                    ->orWhere('last_name', 'ilike', $like)
                    ->orWhere('phone', 'ilike', $like)
                    ->orWhere('email', 'ilike', $like)
                    ->orWhereRaw("(first_name || ' ' || last_name) ilike ?", [$like]);
            })
            ->with('orgUnit:id,name,level_label,parent_id')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(100)
            ->get(['id', 'org_unit_id', 'first_name', 'last_name', 'title', 'phone', 'email', 'status', 'spouse_name', 'photo_path']);

        return $matches->map(fn (Member $member) => [
            'member' => $member->only(['id', 'first_name', 'last_name', 'title', 'phone', 'email', 'status', 'spouse_name', 'photo_path']),
            'org_unit' => $member->orgUnit->only(['id', 'name', 'level_label']),
            'pastor' => $this->resolvePastor($member->orgUnit),
        ])->all();
    }

    /**
     * Le pasteur "responsable" d'une eglise locale n'y a pas forcement une
     * affectation directe (il peut etre affecte plus haut, ex. au niveau
     * district) : on remonte donc les ancetres jusqu'a trouver un titulaire
     * actif du role Pasteur, comme le reste de la plateforme le fait deja
     * pour les permissions (voir connaissance-technique.md, "si un
     * utilisateur n'a pas acces... aucune de ses affectations actives ne
     * porte le role... sur ce noeud OU UN DE SES ANCETRES").
     */
    private function resolvePastor(?OrgUnit $orgUnit): ?array
    {
        $node = $orgUnit;

        while ($node) {
            $affectation = $node->affectations()
                ->where('status', 'active')
                ->whereHas('role', fn ($q) => $q->where('code', Role::PASTEUR))
                ->with('user:id,name,phone')
                ->first();

            if ($affectation && $affectation->user) {
                return ['name' => $affectation->user->name, 'phone' => $affectation->user->phone];
            }

            $node = $node->parent;
        }

        return null;
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageMembers', $orgUnit);

        return Inertia::render('Members/Create', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'honorificTitles' => $orgUnit->ministry->honorificTitles(),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);

        $data = $this->validateMember($request);
        $data['photo_path'] = $this->storePhoto($request, 'photo');
        $data['spouse_photo_path'] = $this->storePhoto($request, 'spouse_photo');

        $member = $orgUnit->members()->create([
            ...$data,
            'ministry_id' => $orgUnit->ministry_id,
            'status' => 'active',
        ]);

        // Corrige le 2026-09-12 (retour du ministere, module Parcours de
        // disciple) : "dès qu'un membre a enregistré nouvellement, qu'on
        // puisse l'attribuer par défaut nouveau converti" - evite un
        // aller-retour manuel systematique dans Parcours de disciple pour
        // un cas qui est la norme. Reste une hypothese par defaut : rien
        // n'empeche d'ajouter ensuite une etape plus avancee si ce membre
        // rejoint l'eglise deja plus loin dans son parcours.
        DiscipleshipStageRecorder::record($member, 'nouveau_converti', $member->joined_at?->toDateString());

        // Corrige le 2026-09-12 (retour du ministere, valable pour TOUS les
        // modules d'enregistrement) : confirmer clairement la reussite,
        // toujours en vert - jamais en rouge, reserve aux erreurs.
        return redirect()->route('members.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Membre enregistré.');
    }

    public function edit(OrgUnit $orgUnit, Member $member): Response
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($member->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Members/Edit', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'member' => $member,
            'honorificTitles' => $orgUnit->ministry->honorificTitles(),
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, Member $member): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($member->org_unit_id === $orgUnit->id, 404);

        $data = $this->validateMember($request);
        $data['status'] = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive'],
        ])['status'];

        if ($request->boolean('remove_photo') && $member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
            $data['photo_path'] = null;
        } elseif ($request->hasFile('photo')) {
            if ($member->photo_path) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $data['photo_path'] = $this->storePhoto($request, 'photo');
        }

        if ($request->boolean('remove_spouse_photo') && $member->spouse_photo_path) {
            Storage::disk('public')->delete($member->spouse_photo_path);
            $data['spouse_photo_path'] = null;
        } elseif ($request->hasFile('spouse_photo')) {
            if ($member->spouse_photo_path) {
                Storage::disk('public')->delete($member->spouse_photo_path);
            }
            $data['spouse_photo_path'] = $this->storePhoto($request, 'spouse_photo');
        }

        $member->update($data);

        return redirect()->route('members.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Membre mis à jour.');
    }

    public function destroy(OrgUnit $orgUnit, Member $member): RedirectResponse
    {
        $this->authorize('manageMembers', $orgUnit);
        abort_unless($member->org_unit_id === $orgUnit->id, 404);

        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }
        if ($member->spouse_photo_path) {
            Storage::disk('public')->delete($member->spouse_photo_path);
        }

        $member->delete();

        return redirect()->route('members.index', ['orgUnit' => $orgUnit->id])
            ->with('success', 'Membre retiré.');
    }

    private function validateMember(Request $request): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', 'string', 'in:M,F'],
            'birth_date' => ['nullable', 'date'],
            'joined_at' => ['nullable', 'date'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:'.self::MAX_PHOTO_KB],
            'spouse_photo' => ['nullable', 'image', 'max:'.self::MAX_PHOTO_KB],
        ]);

        unset($data['photo'], $data['spouse_photo']);

        return $data;
    }

    private function storePhoto(Request $request, string $field): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return $request->file($field)->store('membres/photos', 'public');
    }
}

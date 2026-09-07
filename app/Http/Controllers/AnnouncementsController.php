<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\OrgUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Diffusion des annonces, du haut vers le bas (point 07). Symetrique de la
 * remontee des rapports (point 06) : meme comparaison de chemins, sens de
 * lecture inverse. Une annonce est visible depuis un noeud si son propre
 * noeud de publication est un ancetre de ce noeud (ou ce noeud lui-meme) -
 * exactement l'inverse de OrgUnitPolicy::view(), qui verifie que l'AFFECTATION
 * de l'utilisateur est ancetre du noeud consulte.
 */
class AnnouncementsController extends Controller
{
    // Stockage local (disque "public") pour l'instant : le passage a un
    // stockage S3-compatible (deja prevu dans la pile technique) n'exigera
    // aucun changement de schema, seulement un changement de disque Laravel.
    private const MAX_ATTACHMENT_KB = 10240;

    public function index(Request $request, OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        $announcements = Announcement::whereHas('orgUnit', function ($q) use ($orgUnit) {
            $q->whereRaw('org_units.path @> ?::ltree', [$orgUnit->path]);
        })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->with(['orgUnit:id,name,level_label', 'author:id,name'])
            ->withCount('reads')
            ->orderByDesc('created_at')
            ->get();

        $readAnnouncementIds = AnnouncementRead::where('user_id', $request->user()->id)
            ->whereIn('announcement_id', $announcements->pluck('id'))
            ->pluck('announcement_id');

        return Inertia::render('Annonces/Index', [
            'orgUnit' => $orgUnit,
            'announcements' => $announcements,
            'readAnnouncementIds' => $readAnnouncementIds,
            'canManage' => $request->user()->can('manageAnnouncements', $orgUnit),
        ]);
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('manageAnnouncements', $orgUnit);

        return Inertia::render('Annonces/Create', [
            'orgUnit' => $orgUnit,
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageAnnouncements', $orgUnit);

        $data = $this->validateAnnouncement($request);
        $attachment = $this->storeAttachment($request);

        $orgUnit->announcements()->create($data + $attachment + [
            'ministry_id' => $orgUnit->ministry_id,
            'author_id' => $request->user()->id,
        ]);

        return redirect()->route('annonces.index', ['orgUnit' => $orgUnit->id]);
    }

    public function edit(OrgUnit $orgUnit, Announcement $announcement): Response
    {
        $this->authorize('manageAnnouncements', $orgUnit);
        abort_unless($announcement->org_unit_id === $orgUnit->id, 404);

        return Inertia::render('Annonces/Edit', [
            'orgUnit' => $orgUnit,
            'announcement' => $announcement,
        ]);
    }

    public function update(Request $request, OrgUnit $orgUnit, Announcement $announcement): RedirectResponse
    {
        $this->authorize('manageAnnouncements', $orgUnit);
        abort_unless($announcement->org_unit_id === $orgUnit->id, 404);

        $data = $this->validateAnnouncement($request);

        if ($request->boolean('remove_attachment') && $announcement->attachment_path) {
            Storage::disk('public')->delete($announcement->attachment_path);
            $data['attachment_path'] = null;
            $data['attachment_original_name'] = null;
            $data['attachment_mime'] = null;
        }

        if ($request->hasFile('attachment')) {
            if ($announcement->attachment_path) {
                Storage::disk('public')->delete($announcement->attachment_path);
            }
            $data = array_merge($data, $this->storeAttachment($request));
        }

        $announcement->update($data);

        return redirect()->route('annonces.index', ['orgUnit' => $orgUnit->id]);
    }

    public function destroy(OrgUnit $orgUnit, Announcement $announcement): RedirectResponse
    {
        $this->authorize('manageAnnouncements', $orgUnit);
        abort_unless($announcement->org_unit_id === $orgUnit->id, 404);

        if ($announcement->attachment_path) {
            Storage::disk('public')->delete($announcement->attachment_path);
        }

        $announcement->delete();

        return redirect()->route('annonces.index', ['orgUnit' => $orgUnit->id]);
    }

    /**
     * Accuse de lecture (point 07). Enregistre pour toute annonce, mais
     * l'auteur ne consulte la liste des lecteurs que pour celles marquees
     * importantes (cf. Annonces/Index.vue).
     */
    public function markRead(Request $request, OrgUnit $orgUnit, Announcement $announcement): RedirectResponse
    {
        $this->authorize('view', $orgUnit);

        AnnouncementRead::firstOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $request->user()->id],
            ['ministry_id' => $announcement->ministry_id, 'read_at' => now()]
        );

        return back();
    }

    private function validateAnnouncement(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'important' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'max:'.self::MAX_ATTACHMENT_KB],
        ]);

        unset($data['attachment']);
        $data['important'] = $request->boolean('important');

        return $data;
    }

    private function storeAttachment(Request $request): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        $file = $request->file('attachment');
        $path = $file->store('annonces', 'public');

        return [
            'attachment_path' => $path,
            'attachment_original_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getMimeType(),
        ];
    }
}

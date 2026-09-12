<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Models\OrgUnitDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sous-module "Archives" de Documents (chantier 2026-09-12, retour du
 * ministere : "que chaque eglise locale... puisse enregistrer ces
 * documents propres comme archives, stocker ces documents"). Chaque
 * entite (n'importe quel rang) garde SES PROPRES documents - jamais
 * ceux d'une entite rattachee ni d'un ancetre, exactement comme demande
 * ("selon son niveau dans l'ordre que chaque institution puisse
 * enregistrer ces documents propres").
 *
 * Stockage sur le disque "local" (prive) plutot que "public" (voir
 * migration org_unit_documents) : un document d'archive peut etre
 * sensible, il n'est donc jamais expose par une URL publique, seulement
 * par un telechargement authentifie et autorise ci-dessous.
 */
class OrgUnitDocumentsController extends Controller
{
    private const MAX_SIZE_KB = 20480; // 20 Mo

    private const ALLOWED_MIMES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,webp';

    public function index(OrgUnit $orgUnit): Response
    {
        $this->authorize('view', $orgUnit);

        return Inertia::render('Documents/Archives', [
            'orgUnit' => $orgUnit->only(['id', 'name', 'level_label']),
            'canManage' => request()->user()->can('manageDocuments', $orgUnit),
            'documents' => $orgUnit->documents()
                ->with('uploader:id,name')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (OrgUnitDocument $d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'original_name' => $d->original_name,
                    'mime' => $d->mime,
                    'size' => $d->size,
                    'uploader_name' => $d->uploader?->name,
                    'created_at' => $d->created_at,
                ]),
        ]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('manageDocuments', $orgUnit);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:'.self::MAX_SIZE_KB, 'mimes:'.self::ALLOWED_MIMES],
        ]);

        $file = $request->file('file');
        $path = $file->store("org-units/{$orgUnit->id}/documents", 'local');

        $orgUnit->documents()->create([
            'ministry_id' => $orgUnit->ministry_id,
            'uploaded_by' => $request->user()->id,
            'title' => $data['title'],
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return back()->with('success', 'Document enregistré dans les archives.');
    }

    public function download(OrgUnit $orgUnit, OrgUnitDocument $document): StreamedResponse
    {
        $this->authorize('view', $orgUnit);
        abort_unless($document->org_unit_id === $orgUnit->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    public function destroy(OrgUnit $orgUnit, OrgUnitDocument $document): RedirectResponse
    {
        $this->authorize('manageDocuments', $orgUnit);
        abort_unless($document->org_unit_id === $orgUnit->id, 404);

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Document supprimé des archives.');
    }
}

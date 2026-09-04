<?php

namespace App\Http\Controllers;

use App\Models\OrgUnit;
use App\Services\AttachmentCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controleur volontairement mince (point 10) : il valide l'entree, appelle
 * le service, renvoie une reponse - la logique reste dans
 * AttachmentCodeService, reutilisable demain par une API mobile.
 */
class AttachmentCodeController extends Controller
{
    public function __construct(private AttachmentCodeService $attachmentCodes)
    {
    }

    public function create(OrgUnit $orgUnit): Response
    {
        $this->authorize('issueAttachmentCode', $orgUnit);

        return Inertia::render('OrgUnits/IssueAttachmentCode', ['orgUnit' => $orgUnit]);
    }

    public function store(Request $request, OrgUnit $orgUnit): RedirectResponse
    {
        $this->authorize('issueAttachmentCode', $orgUnit);

        $validated = $request->validate([
            'target_level_rank' => ['required', 'integer', 'min:0', 'max:6'],
            'valid_for_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ]);

        [$attachmentCode, $plainCode] = $this->attachmentCodes->issue(
            $orgUnit,
            $validated['target_level_rank'],
            $request->user(),
            $validated['valid_for_hours'] ?? 72,
        );

        return back()->with('plain_code', $plainCode)->with('attachment_code_id', $attachmentCode->id);
    }

    public function redeem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'code_short' => ['required', 'string', 'max:255'],
            'level_label' => ['required', 'string', 'max:255'],
        ]);

        $newUnit = $this->attachmentCodes->consume($validated['code'], [
            'name' => $validated['name'],
            'code' => $validated['code_short'],
            'level_label' => $validated['level_label'],
        ], $request->user());

        return redirect()
            ->route('dashboard', ['orgUnit' => $newUnit->id])
            ->with('success', "« {$newUnit->name} » a été créée et rattachée automatiquement.");
    }
}

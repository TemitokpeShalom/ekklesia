<?php

namespace App\Http\Controllers;

use App\Models\AssistantConversation;
use App\Models\Ministry;
use App\Models\OrgUnit;
use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API JSON du widget d'assistant IA (voir resources/js/Components/Assistant
 * /AssistantWidget.vue) - endpoints appeles en arriere-plan (fetch), jamais
 * des visites Inertia : le widget flotte au-dessus de la page en cours,
 * il ne doit jamais provoquer de navigation.
 *
 * Toutes ces routes vivent dans le groupe auth+tenant.context (voir
 * routes/web.php), donc apres passage de SetTenantContext : la RLS est
 * deja active pour la duree de la requete. Chaque action verifie EN PLUS
 * que la conversation demandee appartient bien a l'utilisateur courant
 * (voir AssistantService, une conversation est privee a son auteur - la
 * RLS isole les ministeres entre eux, pas les personnes d'un meme
 * ministere).
 */
class AssistantController extends Controller
{
    public function __construct(private AssistantService $assistant)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $ministry = $this->currentMinistry($request);
        $orgUnit = $this->resolveOrgUnit($request, $ministry);

        $conversation = $this->assistant->activeConversation($request->user(), $ministry, $orgUnit);

        return $this->conversationResponse($conversation, $ministry);
    }

    public function startNew(Request $request): JsonResponse
    {
        $ministry = $this->currentMinistry($request);
        $orgUnit = $this->resolveOrgUnit($request, $ministry);

        $conversation = $this->assistant->startConversation($request->user(), $ministry, $orgUnit);

        return $this->conversationResponse($conversation, $ministry);
    }

    public function sendMessage(Request $request, AssistantConversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);

        $ministry = $this->currentMinistry($request);
        abort_unless($conversation->ministry_id === $ministry->id, 404);

        if ($this->assistant->dailyLimitReached($ministry)) {
            return response()->json([
                'error' => "Le quota de messages de l'assistant pour aujourd'hui a été atteint pour votre ministère. Réessayez demain, ou contactez l'équipe technique si c'est bloquant.",
            ], 429);
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:8000'],
            'files' => ['nullable', 'array', 'max:3'],
            'files.*' => [
                'file',
                'max:'.config('assistant.max_attachment_kb'),
                'mimes:png,jpg,jpeg,webp,pdf',
            ],
        ]);

        abort_if(
            blank($validated['message'] ?? null) && empty($validated['files'] ?? []),
            422,
            'Message vide.'
        );

        $orgUnit = $this->resolveOrgUnit($request, $ministry);

        $reply = $this->assistant->reply(
            $conversation,
            $request->user(),
            $ministry,
            $orgUnit,
            $validated['message'] ?? null,
            $validated['files'] ?? []
        );

        return response()->json([
            'reply' => [
                'id' => $reply->id,
                'role' => $reply->role,
                'content' => $reply->content,
                'created_at' => $reply->created_at,
            ],
        ]);
    }

    public function flagFeedback(Request $request, AssistantConversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);

        $ministry = $this->currentMinistry($request);
        abort_unless($conversation->ministry_id === $ministry->id, 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $this->assistant->flagToDeveloper($conversation, $request->user(), $ministry, $validated['message']);

        return response()->json(['ok' => true]);
    }

    private function conversationResponse(AssistantConversation $conversation, Ministry $ministry): JsonResponse
    {
        return response()->json([
            'assistant' => [
                'name' => config('assistant.name'),
                'tagline' => config('assistant.tagline'),
            ],
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
            ],
            'messages' => $conversation->messages()->get()->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'attachments' => collect($m->attachments ?? [])->map(fn ($a) => [
                    'original_name' => $a['original_name'],
                    'mime' => $a['mime'],
                ]),
                'created_at' => $m->created_at,
            ]),
            'dailyLimitReached' => $this->assistant->dailyLimitReached($ministry),
        ]);
    }

    // Point 04 : jamais de requete "ministere courant" au hasard - toujours
    // celui fixe en session pour CETTE requete (le meme que celui deja
    // impose a la base par SetTenantContext).
    private function currentMinistry(Request $request): Ministry
    {
        $ministryId = $request->session()->get('current_ministry_id');

        abort_unless($ministryId, 403, 'Aucun ministère en contexte.');

        return Ministry::findOrFail($ministryId);
    }

    // org_unit_id est purement informatif (voir AssistantConversation) :
    // fourni par le widget depuis les props Inertia de la page en cours
    // quand il y en a une, jamais une donnee de securite - on verifie
    // seulement qu'il appartient bien au ministere courant avant de le
    // rattacher a la conversation, pour eviter d'y stocker n'importe quoi.
    private function resolveOrgUnit(Request $request, Ministry $ministry): ?OrgUnit
    {
        $orgUnitId = $request->input('org_unit_id');

        if (! $orgUnitId) {
            return null;
        }

        return OrgUnit::where('id', $orgUnitId)->where('ministry_id', $ministry->id)->first();
    }
}

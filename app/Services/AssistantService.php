<?php

namespace App\Services;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\AssistantPlatformFeedback;
use App\Models\HelpArticle;
use App\Models\Ministry;
use App\Models\OrgUnit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Assistant IA integre (chantier du 2026-09-10, "autres corrections" -
 * point 1). Toute la logique d'appel a l'API Claude (Anthropic) vit ici,
 * jamais dans le controleur : AssistantController reste un traducteur
 * HTTP <-> service, comme le reste de l'application (voir
 * MinistryRegistrationService, InvitationService).
 *
 * Isolation multi-ministere (point 04) : ce service ne recoit QUE le
 * ministere/utilisateur/noeud DEJA resolus par le controleur a partir de la
 * requete authentifiee courante (donc apres passage de SetTenantContext).
 * Il ne fait jamais de requete "tous ministeres" - la RLS l'en empecherait
 * de toute facon, mais l'intention doit rester explicite ici aussi (voir
 * connaissance-technique.md, section securite, transmise a l'IA elle-meme).
 */
class AssistantService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    // Une conversation appartient a un seul utilisateur (voir
    // AssistantConversation) : toute requete ci-dessous filtre par
    // user_id en plus du ministry_id impose par la RLS - la RLS isole les
    // ministeres entre eux, pas les personnes d'un meme ministere entre
    // elles.
    public function activeConversation(User $user, Ministry $ministry, ?OrgUnit $orgUnit): AssistantConversation
    {
        $conversation = AssistantConversation::where('user_id', $user->id)
            ->orderByDesc('last_message_at')
            ->first();

        if ($conversation) {
            return $conversation;
        }

        return $this->startConversation($user, $ministry, $orgUnit);
    }

    public function startConversation(User $user, Ministry $ministry, ?OrgUnit $orgUnit): AssistantConversation
    {
        return AssistantConversation::create([
            'ministry_id' => $ministry->id,
            'user_id' => $user->id,
            'org_unit_id' => $orgUnit?->id,
        ]);
    }

    public function conversationFor(User $user, string $conversationId): ?AssistantConversation
    {
        return AssistantConversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Plafond de cout (point 5 de la demande) : la cle API est celle de
     * Martin, partagee par tous les ministeres de la plateforme - un seul
     * ministere ne doit jamais pouvoir l'epuiser pour tous les autres.
     * Compte les messages UTILISATEUR (pas les reponses de l'assistant)
     * envoyes aujourd'hui, tous utilisateurs de ce ministere confondus.
     */
    public function dailyLimitReached(Ministry $ministry): bool
    {
        $limit = (int) config('assistant.daily_message_limit_per_ministry');

        if ($limit <= 0) {
            return false;
        }

        $sentToday = AssistantMessage::where('ministry_id', $ministry->id)
            ->where('role', AssistantMessage::ROLE_USER)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return $sentToday >= $limit;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function reply(AssistantConversation $conversation, User $user, Ministry $ministry, ?OrgUnit $orgUnit, ?string $text, array $files): AssistantMessage
    {
        $attachments = array_map(
            fn (UploadedFile $file) => $this->storeAttachment($file, $conversation->id),
            $files
        );

        $userMessage = AssistantMessage::create([
            'ministry_id' => $ministry->id,
            'conversation_id' => $conversation->id,
            'role' => AssistantMessage::ROLE_USER,
            'content' => $text,
            'attachments' => $attachments,
        ]);

        $conversation->update(['last_message_at' => now()]);

        if ($conversation->title === null && $text) {
            $conversation->update(['title' => Str::limit($text, 60)]);
        }

        try {
            [$replyText, $usage] = $this->callClaude($conversation, $user, $ministry, $orgUnit);

            return AssistantMessage::create([
                'ministry_id' => $ministry->id,
                'conversation_id' => $conversation->id,
                'role' => AssistantMessage::ROLE_ASSISTANT,
                'content' => $replyText,
                'input_tokens' => $usage['input_tokens'] ?? null,
                'output_tokens' => $usage['output_tokens'] ?? null,
            ]);
        } catch (RuntimeException $e) {
            Log::error('assistant.claude_call_failed', ['message' => $e->getMessage(), 'conversation_id' => $conversation->id]);

            return AssistantMessage::create([
                'ministry_id' => $ministry->id,
                'conversation_id' => $conversation->id,
                'role' => AssistantMessage::ROLE_ASSISTANT,
                'content' => "Désolé, je n'arrive pas à répondre pour le moment (problème technique côté assistant). Réessayez dans quelques instants ; si cela persiste, signalez-le à l'équipe technique.",
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function flagToDeveloper(AssistantConversation $conversation, User $user, Ministry $ministry, string $message): AssistantPlatformFeedback
    {
        return AssistantPlatformFeedback::create([
            'ministry_id' => $ministry->id,
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'category' => 'signalement',
            'message' => $message,
        ]);
    }

    /**
     * @return array{0: string, 1: array}
     */
    private function callClaude(AssistantConversation $conversation, User $user, Ministry $ministry, ?OrgUnit $orgUnit): array
    {
        $apiKey = config('assistant.api_key');

        if (! $apiKey) {
            // Cle absente du .env : erreur de configuration serveur, pas un
            // probleme reseau - message distinct pour aider Martin au
            // diagnostic (voir les logs).
            throw new RuntimeException('ANTHROPIC_API_KEY absente de la configuration du serveur.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => config('assistant.api_version'),
            'content-type' => 'application/json',
        ])
            ->timeout(60)
            ->post(self::API_URL, [
                'model' => config('assistant.model'),
                'max_tokens' => config('assistant.max_tokens'),
                'system' => $this->systemBlocks($user, $ministry, $orgUnit),
                'messages' => $this->historyBlocks($conversation),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Anthropic API a répondu '.$response->status().' : '.$response->body());
        }

        $data = $response->json();
        $textBlocks = collect($data['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text');

        if ($textBlocks->isEmpty()) {
            throw new RuntimeException('Réponse Anthropic sans bloc texte exploitable.');
        }

        return [$textBlocks->implode("\n"), $data['usage'] ?? []];
    }

    /**
     * Le bloc "system" est un tableau de blocs (pas une simple chaine) pour
     * pouvoir marquer la partie volumineuse et STABLE (manuel + doc
     * technique) avec cache_control : Anthropic met ce bloc en cache cote
     * serveur pendant quelques minutes, ce qui evite de le refacturer en
     * entier a chaque tour d'une meme conversation (point 5 de la demande -
     * maitrise des couts). Seule la partie variable (utilisateur/role/noeud
     * courants) change a chaque appel et reste hors cache.
     */
    private function systemBlocks(User $user, Ministry $ministry, ?OrgUnit $orgUnit): array
    {
        $identity = strtr(
            "Tu es {{assistant_name}}, {{assistant_tagline}}.\n\n",
            [
                '{{assistant_name}}' => config('assistant.name'),
                '{{assistant_tagline}}' => config('assistant.tagline'),
            ]
        );

        $technical = strtr(
            File::get(resource_path('assistant/connaissance-technique.md')),
            [
                '{{assistant_name}}' => config('assistant.name'),
                '{{assistant_tagline}}' => config('assistant.tagline'),
            ]
        );

        $helpArticles = HelpArticle::orderBy('module')->orderBy('order')->get()
            ->map(fn ($a) => "### {$a->module} — {$a->title}\n\n{$a->body}")
            ->implode("\n\n");

        $staticKnowledge = $identity."\n\n".$technical."\n\n## Manuel d'utilisation (contenu affiché aux utilisateurs sous \"Aide\")\n\n".$helpArticles;

        $affectationLabel = $user->activeAffectations()
            ->where('ministry_id', $ministry->id)
            ->with('role')
            ->get()
            ->map(fn ($a) => $a->role->label)
            ->unique()
            ->implode(', ');

        $dynamicContext = "Contexte de la conversation en cours (ne concerne QUE cet utilisateur, dans CE ministère - jamais les autres) :\n"
            ."- Utilisateur : {$user->name}\n"
            ."- Ministère : {$ministry->name}\n"
            .($affectationLabel !== '' ? "- Rôle(s) dans ce ministère : {$affectationLabel}\n" : '')
            .($orgUnit ? "- Écran actuel : {$orgUnit->name} ({$orgUnit->level_label})\n" : '');

        return [
            ['type' => 'text', 'text' => $staticKnowledge, 'cache_control' => ['type' => 'ephemeral']],
            ['type' => 'text', 'text' => $dynamicContext],
        ];
    }

    /**
     * Reconstruit l'historique au format attendu par l'API (sans etat entre
     * appels : tout doit etre renvoye a chaque fois). Fenetre limitee
     * (assistant.history_window) pour maitriser le cout d'un tour a
     * l'autre ; parmi les messages gardes, seules les pieces jointes des
     * DEUX derniers messages utilisateur sont rejointes en octets - au-dela,
     * seule leur description textuelle reste (evite de refacturer des
     * images anciennes a chaque nouveau tour).
     */
    private function historyBlocks(AssistantConversation $conversation): array
    {
        $window = max(1, (int) config('assistant.history_window'));

        $messages = $conversation->messages()
            ->orderByDesc('created_at')
            ->limit($window)
            ->get()
            ->reverse()
            ->values();

        $recentAttachmentCutoffIndex = $messages->count() - 3;

        return $messages->map(function (AssistantMessage $message, int $index) use ($recentAttachmentCutoffIndex) {
            $blocks = [];

            foreach ($message->attachments ?? [] as $attachment) {
                if ($index >= $recentAttachmentCutoffIndex) {
                    $block = $this->attachmentToBlock($attachment);
                    if ($block) {
                        $blocks[] = $block;
                    }
                } else {
                    $blocks[] = ['type' => 'text', 'text' => '[Pièce jointe précédente : '.$attachment['original_name'].' — contenu non renvoyé pour limiter le coût]'];
                }
            }

            if ($message->content) {
                $blocks[] = ['type' => 'text', 'text' => $message->content];
            }

            if ($blocks === []) {
                $blocks[] = ['type' => 'text', 'text' => '(message vide)'];
            }

            return ['role' => $message->role, 'content' => $blocks];
        })->all();
    }

    private function attachmentToBlock(array $attachment): ?array
    {
        if (! Storage::disk('public')->exists($attachment['path'])) {
            return null;
        }

        $bytes = Storage::disk('public')->get($attachment['path']);
        $base64 = base64_encode($bytes);

        if ($attachment['mime'] === 'application/pdf') {
            return [
                'type' => 'document',
                'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $base64],
            ];
        }

        return [
            'type' => 'image',
            'source' => ['type' => 'base64', 'media_type' => $attachment['mime'], 'data' => $base64],
        ];
    }

    /**
     * @return array{path: string, original_name: string, mime: string, size: int}
     */
    private function storeAttachment(UploadedFile $file, string $conversationId): array
    {
        $path = $file->store("assistant-attachments/{$conversationId}", 'public');

        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }
}

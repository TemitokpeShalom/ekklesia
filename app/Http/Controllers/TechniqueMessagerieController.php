<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Boite de reception de l'equipe technique : lire et repondre a tout le monde. */
class TechniqueMessagerieController extends Controller
{
    public function index(Request $request): Response
    {
        $threads = SupportThread::with(['user:id,name,email', 'ministry:id,name'])
            ->whereHas('messages')
            ->orderByDesc('last_message_at')
            ->get();

        $lastBodies = SupportMessage::whereIn('thread_id', $threads->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('thread_id')
            ->map(fn ($g) => $g->last()?->body);

        $selected = null;
        $selectedId = $request->query('fil');

        if ($selectedId) {
            $thread = $threads->firstWhere('id', $selectedId);
            if ($thread) {
                if ($thread->unread_by_staff) {
                    $thread->update(['unread_by_staff' => false]);
                }
                $selected = [
                    'id' => $thread->id,
                    'status' => $thread->status,
                    'user_name' => $thread->user?->name,
                    'user_email' => $thread->user?->email,
                    'ministry_name' => $thread->ministry?->name,
                    'messages' => $thread->messages()->get()->map(fn (SupportMessage $m) => [
                        'id' => $m->id,
                        'from_staff' => $m->from_staff,
                        'body' => $m->body,
                        'created_at' => $m->created_at->toIso8601String(),
                    ]),
                ];
            }
        }

        return Inertia::render('Technique/Messagerie', [
            'threads' => $threads->map(fn (SupportThread $t) => [
                'id' => $t->id,
                'user_name' => $t->user?->name ?? 'Compte supprimé',
                'ministry_name' => $t->ministry?->name,
                'status' => $t->status,
                'unread' => $t->unread_by_staff && $t->id !== $selectedId,
                'last_body' => str($lastBodies[$t->id] ?? '')->limit(70)->toString(),
                'last_message_at' => optional($t->last_message_at)->toIso8601String(),
            ])->values(),
            'selected' => $selected,
        ]);
    }

    public function reply(Request $request, SupportThread $thread): RedirectResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);

        SupportMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $request->user()->id,
            'from_staff' => true,
            'body' => trim($data['message']),
        ]);

        $thread->update(['unread_by_user' => true, 'last_message_at' => now()]);

        return back();
    }

    public function status(Request $request, SupportThread $thread): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:ouvert,clos']]);
        $thread->update($data);

        return back()->with('success', $data['status'] === 'clos' ? 'Discussion clôturée.' : 'Discussion rouverte.');
    }

    public function destroy(SupportThread $thread): RedirectResponse
    {
        $thread->delete();

        return redirect('/technique/messagerie')->with('success', 'Discussion supprimée.');
    }
}

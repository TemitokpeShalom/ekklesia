<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Messagerie directe d'une personne avec l'equipe technique (cote
 * utilisateur, JSON, appelee par le widget). Un seul fil par personne :
 * chacun ne voit que le sien.
 */
class SupportController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $thread = $this->thread($request);

        if ($thread->unread_by_user) {
            $thread->update(['unread_by_user' => false]);
        }

        return $this->payload($thread);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);

        $thread = $this->thread($request);

        SupportMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => $request->user()->id,
            'from_staff' => false,
            'body' => trim($data['message']),
        ]);

        $thread->update([
            'ministry_id' => $request->session()->get('current_ministry_id') ?: $thread->ministry_id,
            'status' => 'ouvert',
            'unread_by_staff' => true,
            'last_message_at' => now(),
        ]);

        return $this->payload($thread->fresh());
    }

    public function unread(Request $request): JsonResponse
    {
        $count = SupportThread::where('user_id', $request->user()->id)->where('unread_by_user', true)->count();

        return response()->json(['unread' => $count]);
    }

    private function thread(Request $request): SupportThread
    {
        return SupportThread::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'ministry_id' => $request->session()->get('current_ministry_id'),
                'status' => 'ouvert',
                'unread_by_staff' => false,
            ]
        );
    }

    private function payload(SupportThread $thread): JsonResponse
    {
        return response()->json([
            'messages' => $thread->messages()->get()->map(fn (SupportMessage $m) => [
                'id' => $m->id,
                'from_staff' => $m->from_staff,
                'body' => $m->body,
                'created_at' => $m->created_at,
            ]),
        ]);
    }
}

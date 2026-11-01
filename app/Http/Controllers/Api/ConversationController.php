<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\SocialData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    private const PAGE = 30;

    /** Messages page and chat sidebar. */
    public function index(Request $request, SocialData $social)
    {
        $inbox = $social->inbox($request->user());

        return [
            'friends' => $inbox['friends']->map(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'avatar_url' => UserResource::url($f->avatar),
                'is_online' => (bool) $f->is_online,
                'conversation_id' => $f->conversation_id,
                'unread' => (int) $f->unread_messages_count,
                'preview' => $f->last_message_text,
                'last_message_at' => $f->last_message_at?->toIso8601String(),
            ])->values(),
            'groups' => $inbox['groups']->map(fn ($g) => [
                'id' => $g->id,
                'title' => $g->title ?: 'Group chat',
                'avatar_url' => UserResource::url($g->avatar),
                'unread' => (int) $g->unread_count,
                'preview' => $g->preview,
                'last_message_at' => $g->last_message_at?->toIso8601String(),
            ])->values(),
        ];
    }

    /** Opens a chat: details + the newest messages. Also marks it as read. */
    public function show(Request $request, Conversation $conversation)
    {
        $me = $request->user();

        abort_unless($conversation->hasParticipant($me), 403);

        $conversation->load('participants:id,name,avatar,last_seen_at');
        Message::finalizeStaleCalls($conversation->id);

        $batch = $this->page($conversation, $me);

        $conversation->participants()->updateExistingPivot($me->id, ['last_read_at' => now()]);

        $other = $conversation->type === 'private' ? $conversation->participants->firstWhere('id', '!=', $me->id) : null;

        return [
            'conversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'title' => $conversation->displayTitleFor($me),
                'avatar_url' => UserResource::url($conversation->avatar),
                'members' => UserResource::collection($conversation->participants)->resolve(),
                'other' => $other ? (new UserResource($other))->resolve() : null,
            ],
            'messages' => $batch['messages'],
            'has_more' => $batch['has_more'],
        ];
    }

    /** Older messages: GET /conversations/{id}/messages?before=<oldest id on screen> */
    public function messages(Request $request, Conversation $conversation)
    {
        $me = $request->user();

        abort_unless($conversation->hasParticipant($me), 403);

        return $this->page($conversation, $me, (int) $request->query('before', 0));
    }

    /** Start a chat: 1-to-1 (user_id) or group (title + user_ids[]). */
    public function store(Request $request)
    {
        $me = $request->user();

        if ($request->filled('user_id')) {
            $other = User::findOrFail($request->input('user_id'));

            abort_if($other->id === $me->id, 422, 'You cannot message yourself.');
            abort_unless($me->isFriendsWith($other), 403, 'You can only message friends.');

            return ['conversation_id' => Conversation::findOrCreatePrivate($me, $other)->id];
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'user_ids' => ['required', 'array', 'min:1', 'max:49'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $memberIds = collect($data['user_ids'])->unique()->reject(fn ($id) => $id == $me->id);

        abort_unless($memberIds->diff($me->friendIds())->isEmpty(), 403, 'You can only add friends.');

        $conversation = DB::transaction(function () use ($me, $data, $memberIds) {
            $conversation = Conversation::create(['type' => 'group', 'title' => $data['title'], 'created_by' => $me->id]);
            $conversation->participants()->attach($me->id, ['role' => 'admin']);
            $conversation->participants()->attach($memberIds->all());

            return $conversation;
        });

        return response()->json(['conversation_id' => $conversation->id], 201);
    }

    private function page(Conversation $conversation, User $me, int $before = 0): array
    {
        $batch = $conversation->messages()
            ->visibleTo($me)
            ->with(['user:id,name,avatar', 'replyTo:id,body'])
            ->when($before, fn ($q) => $q->where('id', '<', $before))
            ->orderByDesc('id')
            ->limit(self::PAGE + 1)
            ->get();

        return [
            'messages' => $batch->take(self::PAGE)->reverse()->values(),
            'has_more' => $batch->count() > self::PAGE,
        ];
    }
}

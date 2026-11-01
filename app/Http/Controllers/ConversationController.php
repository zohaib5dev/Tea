<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /** Messages page: every friend with the last message of your chat, newest conversations first. */
    public function index(Request $request)
    {
        return view('chat.index', $this->inbox($request->user()));
    }

    /**
     * Data for the conversation lists (used by the inbox page and the chat sidebar):
     * friends sorted by latest conversation, group chats, and the total unread count.
     */
    private function inbox(User $me): array
    {
        $conversations = $me->conversations()
            ->with(['participants:id,name,avatar,last_seen_at', 'latestMessage.user:id,name'])
            ->orderByDesc('last_message_at')
            ->get()
            ->each(function ($conversation) use ($me) {
                $readAt = $conversation->pivot->last_read_at;

                $conversation->unread_count = $conversation->messages()
                    ->visibleTo($me)
                    ->where('user_id', '!=', $me->id)
                    ->when($readAt, fn ($q) => $q->where('created_at', '>', $readAt))
                    ->count();

                // One-line preview: "You: see you at 5" / "Sent a photo" / "Anna: hello"
                $last = $conversation->latestMessage;
                $conversation->preview = $last
                    ? ($last->type === 'call' ? '' : ($last->user_id === $me->id ? 'You: ' : ($conversation->type === 'group' ? explode(' ', $last->user->name)[0] . ': ' : '')))
                        . $last->previewText()
                    : null;
            });

        // Private chat of each friend, keyed by the friend's id.
        $privateByFriend = $conversations->where('type', 'private')
            ->keyBy(fn ($c) => $c->participants->firstWhere('id', '!=', $me->id)?->id);

        $friends = $me->friends()->get()
            ->each(function ($friend) use ($privateByFriend) {
                $c = $privateByFriend->get($friend->id);

                $friend->setAttribute('conversation_id', $c?->id);
                $friend->setAttribute('unread_messages_count', $c?->unread_count ?? 0);
                $friend->setAttribute('last_message_text', $c?->preview);
                $friend->setAttribute('last_message_at', $c?->last_message_at);
                $friend->setAttribute('last_activity', $c?->last_message_at?->timestamp ?? 0);
            })
            // Latest conversation first, then online friends, then A to Z.
            ->sortBy([['last_activity', 'desc'], ['is_online', 'desc'], ['name', 'asc']])
            ->values();

        return [
            'friends' => $friends,
            'groups' => $conversations->where('type', 'group')->values(),
            'totalUnread' => $conversations->sum('unread_count'),
        ];
    }

    public function show(Request $request, Conversation $conversation)
    {
        $me = $request->user();

        abort_unless($conversation->hasParticipant($me), 403);

        $conversation->load('participants:id,name,avatar,last_seen_at');

        Message::finalizeStaleCalls($conversation->id);

        $messages = $conversation->messages()
            ->visibleTo($me)
            ->with(['user:id,name,avatar', 'replyTo:id,body,user_id'])
            ->latest()
            ->paginate(30);

        $conversation->participants()->updateExistingPivot($me->id, ['last_read_at' => now()]);

        return view('chat.show', array_merge(compact('conversation', 'messages'), $this->inbox($me)));
    }

    /**
     * Start a chat.
     *  - 1-to-1: send user_id (must be a friend)
     *  - group:  send title + user_ids[]
     */
    public function store(Request $request)
    {
        $me = $request->user();

        if ($request->filled('user_id')) {
            $other = User::findOrFail($request->input('user_id'));

            abort_if($other->id === $me->id, 422, 'You cannot message yourself.');
            abort_unless($me->isFriendsWith($other), 403, 'You can only message friends.');

            return redirect()->route('conversations.show', Conversation::findOrCreatePrivate($me, $other));
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'user_ids' => ['required', 'array', 'min:1', 'max:49'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $memberIds = collect($data['user_ids'])->unique()->reject(fn ($id) => $id == $me->id);

        // Only friends can be added to a group.
        abort_unless($memberIds->diff($me->friendIds())->isEmpty(), 403, 'You can only add friends.');

        $conversation = DB::transaction(function () use ($me, $data, $memberIds) {
            $conversation = Conversation::create([
                'type' => 'group',
                'title' => $data['title'],
                'created_by' => $me->id,
            ]);

            $conversation->participants()->attach($me->id, ['role' => 'admin']);
            $conversation->participants()->attach($memberIds->all());

            return $conversation;
        });

        return redirect()->route('conversations.show', $conversation);
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SocialData
{
    /**
     * All friends of $me, sorted: unread messages first, then people you talked with (latest chat first),
     * then online, then A to Z. Each friend gets unread_messages_count and last_message_at.
     */
    public function friendsByActivity(User $me): Collection
    {
        $friends = $me->friends()->get();

        $stats = DB::table('conversations as c')
            ->join('conversation_participants as me', function ($join) use ($me) {
                $join->on('me.conversation_id', '=', 'c.id')->where('me.user_id', $me->id);
            })
            ->join('conversation_participants as other', function ($join) use ($me) {
                $join->on('other.conversation_id', '=', 'c.id')->where('other.user_id', '!=', $me->id);
            })
            ->join('messages as m', 'm.conversation_id', '=', 'c.id')
            ->where('c.type', 'private')
            ->whereNull('m.deleted_at')
            ->groupBy('other.user_id')
            ->selectRaw(
                'other.user_id as friend_id,
                 max(m.created_at) as last_at,
                 sum(case when m.user_id != ? and (me.last_read_at is null or m.created_at > me.last_read_at) then 1 else 0 end) as unread',
                [$me->id]
            )
            ->get()
            ->keyBy('friend_id');

        $friends->each(function ($friend) use ($stats) {
            $row = $stats->get($friend->id);
            $friend->setAttribute('unread_messages_count', (int) ($row->unread ?? 0));
            $friend->setAttribute('last_message_at', $row->last_at ?? null);
        });

        return $friends->sort(fn ($a, $b) =>
            [$b->unread_messages_count > 0, (bool) $b->last_message_at, $b->last_message_at, (bool) $b->is_online, $a->name]
            <=> [$a->unread_messages_count > 0, (bool) $a->last_message_at, $a->last_message_at, (bool) $a->is_online, $b->name]
        )->values();
    }

    /** Messages page data: friends with their private chat, group chats, total unread. */
    public function inbox(User $me): array
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

                $last = $conversation->latestMessage;
                $conversation->preview = $last
                    ? ($last->type === 'call' ? '' : ($last->user_id === $me->id ? 'You: ' : ($conversation->type === 'group' ? explode(' ', $last->user->name)[0] . ': ' : '')))
                        . $last->previewText()
                    : null;
            });

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
            ->sortBy([['last_activity', 'desc'], ['is_online', 'desc'], ['name', 'asc']])
            ->values();

        return [
            'friends' => $friends,
            'groups' => $conversations->where('type', 'group')->values(),
        ];
    }
}

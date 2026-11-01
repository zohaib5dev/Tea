<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $posts = Post::with('user')
            ->withCount(['likes', 'comments'])
            ->withExists(['likes as liked' => fn ($q) => $q->where('user_id', $user->id)])
            ->visibleTo($user)
            ->latest()
            ->paginate(10);

        // AJAX "load more": return just the next batch of post cards.
        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('partials.post-list', compact('posts'))->render(),
                'next' => $posts->nextPageUrl(),
            ]);
        }

        $receivedRequests = $user->receivedFriendships()->pending()->with('sender')->latest()->get();

        // Load ALL friends first. Sorting and limiting happen afterwards,
        // otherwise a friend who messaged you could be cut off before we see their messages.
        $friends = $user->friends()->get();

        // Per friend, from our private chat: when the LAST message was sent (by either of us)
        // and how many of their messages I haven't read. One query for everyone.
        $stats = DB::table('conversations as c')
            ->join('conversation_participants as me', function ($join) use ($user) {
                $join->on('me.conversation_id', '=', 'c.id')->where('me.user_id', $user->id);
            })
            ->join('conversation_participants as other', function ($join) use ($user) {
                $join->on('other.conversation_id', '=', 'c.id')->where('other.user_id', '!=', $user->id);
            })
            ->join('messages as m', 'm.conversation_id', '=', 'c.id')
            ->where('c.type', 'private')
            ->whereNull('m.deleted_at')
            ->groupBy('other.user_id')
            ->selectRaw(
                'other.user_id as friend_id,
                 max(m.created_at) as last_at,
                 sum(case when m.user_id != ? and (me.last_read_at is null or m.created_at > me.last_read_at) then 1 else 0 end) as unread',
                [$user->id]
            )
            ->get()
            ->keyBy('friend_id');

        $friends->each(function ($friend) use ($stats) {
            $row = $stats->get($friend->id);
            $friend->setAttribute('unread_messages_count', (int) ($row->unread ?? 0));
            $friend->setAttribute('last_message_at', $row->last_at ?? null);
        });

        $friends = $friends->sort(fn ($a, $b) =>
            [$b->unread_messages_count > 0, (bool) $b->last_message_at, $b->last_message_at, (bool) $b->is_online, $a->name]
            <=> [$a->unread_messages_count > 0, (bool) $a->last_message_at, $a->last_message_at, (bool) $a->is_online, $b->name]
        )->take(6)->values();

        return view('feed', [
            'posts' => $posts,
            'receivedRequests' => $receivedRequests,
            'friends' => $friends,
            'suggestions' => $user->suggestions(5),
        ]);
    }
}
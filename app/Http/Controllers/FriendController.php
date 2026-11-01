<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FriendController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $friends = $me->friends()->get();

        // Per friend, from our private chat: when the LAST message was sent (by either of us)
        // and how many of their messages I haven't read. One query for everyone.
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

        // Order: 1) friends with unread messages, 2) friends you have talked with (latest chat first),
        // 3) friends you have never talked with: online first, then A to Z.
        $friends = $friends->sort(fn ($a, $b) =>
            [$b->unread_messages_count > 0, (bool) $b->last_message_at, $b->last_message_at, (bool) $b->is_online, $a->name]
            <=> [$a->unread_messages_count > 0, (bool) $a->last_message_at, $a->last_message_at, (bool) $a->is_online, $b->name]
        )->values();

        return view('friends.index', [
            'friends' => $friends,
            'incoming' => $me->receivedFriendships()->pending()->with('sender')->latest()->get(),
            'outgoing' => $me->sentFriendships()->pending()->with('recipient')->latest()->get(),
            'suggestions' => $me->suggestions(12),
        ]);
    }

    /** Send a friend request (or accept one if they already sent you one). */
    public function store(Request $request, User $user)
    {
        $me = $request->user();

        abort_if($me->id === $user->id, 422, 'You cannot add yourself.');

        $existing = Friendship::between($me->id, $user->id)->first();

        if ($existing) {
            abort_if($existing->status === 'blocked', 403);

            if ($existing->status === 'pending' && $existing->recipient_id === $me->id) {
                $existing->update(['status' => 'accepted', 'accepted_at' => now()]);

                return $this->respond($request, 'You are now friends.');
            }

            return $this->respond($request, 'A request already exists.');
        }

        Friendship::create(['sender_id' => $me->id, 'recipient_id' => $user->id]);

        return $this->respond($request, 'Friend request sent.', 201);
    }

    public function accept(Request $request, Friendship $friendship)
    {
        abort_unless($friendship->recipient_id === $request->user()->id && $friendship->status === 'pending', 403);

        $friendship->update(['status' => 'accepted', 'accepted_at' => now()]);

        return $this->respond($request, 'Friend request accepted.');
    }

    public function decline(Request $request, Friendship $friendship)
    {
        abort_unless($friendship->recipient_id === $request->user()->id && $friendship->status === 'pending', 403);

        $friendship->update(['status' => 'declined']);

        return $this->respond($request, 'Friend request declined.');
    }

    /** Unfriend, or cancel a request you sent. Blocks are left untouched. */
    public function destroy(Request $request, User $user)
    {
        Friendship::between($request->user()->id, $user->id)->where('status', '!=', 'blocked')->delete();

        return $this->respond($request, 'Removed.');
    }

    private function respond(Request $request, string $message, int $status = 200)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], $status)
            : back()->with('status', $message);
    }
}
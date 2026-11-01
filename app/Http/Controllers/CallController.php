<?php

namespace App\Http\Controllers;

use App\Events\CallSignal;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallController extends Controller
{
    /**
     * Relay a WebRTC signaling message to the other person of a private chat,
     * and keep the call's entry in the chat up to date (ringing, ongoing, ended with duration, missed, declined).
     * The audio and video themselves travel directly between the two browsers.
     */
    public function signal(Request $request, Conversation $conversation)
    {
        $me = $request->user();

        abort_unless($conversation->type === 'private' && $conversation->hasParticipant($me), 403);

        $data = $request->validate([
            'type' => ['required', 'in:invite,accept,decline,busy,end,offer,answer,candidate,connected'],
            'call_id' => ['required', 'string', 'max:64'],
            'video' => ['nullable', 'boolean'],
            // SDP / ICE data is sent as a JSON string so Laravel's TrimStrings cannot damage the line endings.
            'data' => ['nullable', 'string', 'max:30000'],
        ]);

        $recipientId = DB::table('conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $me->id)
            ->value('user_id');

        abort_unless($recipientId, 404);

        $this->recordCall($conversation, $me, $data['type'], $data['call_id'], (bool) ($data['video'] ?? false));

        // "connected" is only for the call log; the other browser does not need it.
        if ($data['type'] === 'connected') {
            return response()->noContent();
        }

        try {
            event(new CallSignal($recipientId, [
                'type' => $data['type'],
                'call_id' => $data['call_id'],
                'conversation_id' => $conversation->id,
                'video' => (bool) ($data['video'] ?? false),
                'data' => $data['data'] ?? null,
                'from' => ['id' => $me->id, 'name' => $me->name, 'avatar' => $me->avatar],
            ]));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => config('app.debug')
                    ? 'Broadcast failed: ' . $e->getMessage()
                    : 'The real-time server is not reachable.',
            ], 503);
        }

        return response()->noContent();
    }

    /** Create or update the call's message in the chat. Never allowed to break the call itself. */
    private function recordCall(Conversation $conversation, User $me, string $type, string $callId, bool $video): void
    {
        try {
            if ($type === 'invite') {
                $message = Message::firstOrCreate(
                    ['conversation_id' => $conversation->id, 'call_id' => $callId],
                    ['user_id' => $me->id, 'type' => 'call', 'call_kind' => $video ? 'video' : 'voice', 'call_status' => 'ringing']
                );

                if ($message->wasRecentlyCreated) {
                    $conversation->update(['last_message_at' => now()]);
                    $conversation->participants()->updateExistingPivot($me->id, ['last_read_at' => now()]);
                    event(new MessageSent($message));
                }

                return;
            }

            $message = Message::where('conversation_id', $conversation->id)
                ->where('type', 'call')->where('call_id', $callId)->first();

            if (! $message) {
                return;
            }

            $isCaller = $message->user_id === $me->id;
            $status = $message->call_status;

            switch ($type) {
                case 'accept':
                    if ($status === 'ringing' && ! $isCaller) {
                        $message->call_status = 'ongoing';
                    }
                    break;

                case 'connected':   // the browsers are really talking now: the duration starts here
                    if ($status === 'ongoing' && ! $message->call_connected_at) {
                        $message->call_connected_at = now();
                    }
                    break;

                case 'decline':
                    if ($status === 'ringing' && ! $isCaller) {
                        $message->call_status = 'declined';
                    }
                    break;

                case 'busy':
                    if ($status === 'ringing' && ! $isCaller) {
                        $message->call_status = 'missed';
                    }
                    break;

                case 'end':
                    if ($status === 'ringing') {
                        $message->call_status = $isCaller ? 'missed' : 'declined';   // caller gave up, or callee closed it
                    } elseif ($status === 'ongoing') {
                        $message->call_status = 'ended';
                        $message->call_duration = $message->call_connected_at
                            ? max(0, (int) round($message->call_connected_at->diffInSeconds(now())))
                            : 0;
                    }
                    break;

                default:
                    return;   // offer, answer, candidate: nothing to record
            }

            if ($message->isDirty()) {
                $message->save();
                event(new MessageUpdated($message));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

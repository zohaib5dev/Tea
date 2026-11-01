<?php

namespace App\Http\Controllers;

use App\Events\MessageDeleted;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    /** Safety-net polling: GET /messages/{conversation}/poll?after=<last message id> */
    public function index(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->hasParticipant($request->user()), 403);

        Message::finalizeStaleCalls($conversation->id);

        $messages = $conversation->messages()
            ->with(['user:id,name,avatar', 'replyTo:id,body'])
            ->visibleTo($request->user())
            ->where('id', '>', (int) $request->query('after', 0))
            ->oldest()
            ->limit(100)
            ->get();

        $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return response()->json($messages);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $me = $request->user();

        abort_unless($conversation->hasParticipant($me), 403);

        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            'reply_to_id' => ['nullable', Rule::exists('messages', 'id')->where('conversation_id', $conversation->id)],
        ]);

        $path = $type = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mime = (string) $file->getMimeType();
            $path = $file->store('chat', 'public');
            $type = match (true) {
                str_starts_with($mime, 'image/') => 'image',
                str_starts_with($mime, 'video/') => 'video',
                str_starts_with($mime, 'audio/') => 'audio',
                default => 'file',
            };
        }

        $message = $conversation->messages()->create([
            'user_id' => $me->id,
            'body' => $data['body'] ?? null,
            'reply_to_id' => $data['reply_to_id'] ?? null,
            'attachment_path' => $path,
            'attachment_type' => $type,
        ]);

        $conversation->update(['last_message_at' => now()]);
        $conversation->participants()->updateExistingPivot($me->id, ['last_read_at' => now()]);

        $message->load(['user:id,name,avatar', 'replyTo:id,body']);

        // Push it to everyone in real time.
        $this->broadcastSafely(fn () => new MessageSent($message));

        if ($request->expectsJson()) {
            return response()->json($message, 201);
        }

        return back();
    }

    /** The chat is open and a message was seen: clear the unread count. */
    public function read(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->hasParticipant($request->user()), 403);

        $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return response()->noContent();
    }

    /** "Delete for me": any member can hide a message from their own view. Nobody else is affected. */
    public function hide(Request $request, Message $message)
    {
        abort_unless($message->conversation->hasParticipant($request->user()), 403);

        DB::table('message_deletions')->insertOrIgnore([
            'message_id' => $message->id,
            'user_id' => $request->user()->id,
            'uuid' => (string) Str::uuid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->noContent();
    }

    /** "Delete for everyone": only the sender can do this. */
    public function destroy(Request $request, Message $message)
    {
        abort_unless($message->user_id === $request->user()->id, 403);

        $messageId = $message->id;
        $conversationId = $message->conversation_id;

        $message->delete();

        $this->broadcastSafely(fn () => new MessageDeleted($messageId, $conversationId));

        return $request->expectsJson() ? response()->noContent() : back();
    }

    /**
     * If the websocket server is down, the message is still saved and sent.
     * The chat page falls back to polling in that case.
     */
    private function broadcastSafely(\Closure $makeEvent): void
    {
        try {
            event($makeEvent());
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

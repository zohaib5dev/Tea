<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Broadcast immediately (no queue worker needed) to:
 *  - private-conversation.{id}  everybody who has this chat open
 *  - private-user.{id}          each recipient, so their sidebar and unread badges update
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Message $message)
    {
        $this->message->loadMissing(['user:id,name,avatar', 'replyTo:id,body', 'conversation:id,type,title']);
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('conversation.' . $this->message->conversation_id)];

        $recipientIds = DB::table('conversation_participants')
            ->where('conversation_id', $this->message->conversation_id)
            ->where('user_id', '!=', $this->message->user_id)
            ->whereNull('left_at')
            ->pluck('user_id');

        foreach ($recipientIds as $id) {
            $channels[] = new PrivateChannel('user.' . $id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $m = $this->message;
        $isGroup = $m->conversation->type === 'group';

        $what = $m->previewText();
        $who = ($isGroup && $m->type !== 'call') ? explode(' ', $m->user->name)[0] . ': ' : '';

        return [
            // Same shape the chat page already renders from the AJAX response.
            'message' => Arr::except($m->toArray(), ['conversation']),
            'conversation' => ['id' => $m->conversation_id, 'type' => $m->conversation->type],
            'preview' => $who . $what,   // one-line text for the sidebar
        ];
    }
}

<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/** One WebRTC signaling message (invite, accept, offer, answer, candidate, end...) for one person. */
class CallSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $recipientId, public array $payload)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->recipientId)];
    }

    public function broadcastAs(): string
    {
        return 'call.signal';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}

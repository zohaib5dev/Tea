<?php

namespace App\Events;

/** Same payload as MessageSent, but tells the chat to REDRAW an existing message (a call that ended, for example). */
class MessageUpdated extends MessageSent
{
    public function broadcastAs(): string
    {
        return 'message.updated';
    }
}

<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
// Only people inside a conversation may listen to it (and whisper "typing" in it).
Broadcast::channel('conversation.{conversation}', function ($user, Conversation $conversation) {
    return $conversation->hasParticipant($user);
});

// Each person's own channel: new-message notifications for the sidebar and badges.
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

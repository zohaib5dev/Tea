<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static function url(?string $path): ?string
    {
        return $path ? asset('storage/' . $path) : null;
    }

    public function toArray(Request $request): array
    {
        $u = $this->resource;

        return [
            'id' => $u->id,
            'is_admin' => (bool) $u->getAttribute('is_admin'),
            'name' => $u->name,
            'bio' => $u->getAttribute('bio'),
            'avatar_url' => self::url($u->getAttribute('avatar')),
            'cover_url' => self::url($u->getAttribute('cover_photo')),
            'is_online' => (bool) $u->is_online,
            'joined_at' => $u->created_at?->toIso8601String(),
            // Extra values that some endpoints attach to a user:
            'mutual_friends_count' => $this->when($u->getAttribute('mutual_friends_count') !== null, fn () => (int) $u->getAttribute('mutual_friends_count')),
            'unread' => $this->when($u->getAttribute('unread_messages_count') !== null, fn () => (int) $u->getAttribute('unread_messages_count')),
            'last_message_at' => $this->when($u->getAttribute('last_message_at') !== null, fn () => (string) $u->getAttribute('last_message_at')),
        ];
    }
}

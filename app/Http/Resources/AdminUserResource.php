<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $u = $this->resource;
        $iso = fn ($v) => $v ? Carbon::parse($v)->toIso8601String() : null;

        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'avatar_url' => UserResource::url($u->getAttribute('avatar')),
            'is_admin' => (bool) $u->getAttribute('is_admin'),
            'banned' => ! empty($u->getAttribute('banned_at')),
            'banned_at' => $iso($u->getAttribute('banned_at')),
            'ban_reason' => $u->getAttribute('ban_reason'),
            'is_online' => (bool) $u->is_online,
            'created_at' => $iso($u->created_at),
            'last_seen_at' => $iso($u->getAttribute('last_seen_at')),
            'posts_count' => $this->when($u->getAttribute('posts_count') !== null, fn () => (int) $u->getAttribute('posts_count')),
        ];
    }
}

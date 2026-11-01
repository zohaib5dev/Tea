<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $p = $this->resource;
        // Your column is called "media" (change to "image" if that is what your posts table uses).
        $media = $p->getAttribute('media') ?? $p->getAttribute('image');

        return [
            'id' => $p->id,
            'body' => $p->body,
            'visibility' => $p->visibility,
            'media_url' => UserResource::url($media),
            'created_at' => $p->created_at?->toIso8601String(),
            'edited' => $p->updated_at && $p->created_at && $p->updated_at->gt($p->created_at->copy()->addSeconds(10)),
            'likes_count' => (int) ($p->getAttribute('likes_count') ?? 0),
            'comments_count' => (int) ($p->getAttribute('comments_count') ?? 0),
            'liked' => (bool) ($p->getAttribute('liked') ?? false),
            'mine' => $request->user()?->id === $p->user_id,
            'user' => (new UserResource($p->user))->resolve(),
        ];
    }
}

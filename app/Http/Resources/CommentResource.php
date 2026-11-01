<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $c = $this->resource;

        return [
            'id' => $c->id,
            'body' => $c->body,
            'parent_id' => $c->parent_id,
            'created_at' => $c->created_at?->toIso8601String(),
            'can_delete' => (bool) $c->getAttribute('can_delete'),
            'user' => (new UserResource($c->user))->resolve(),
            'replies' => $c->relationLoaded('replies')
                ? $c->replies->map(fn ($r) => (new CommentResource($r))->resolve())->values()
                : [],
        ];
    }
}

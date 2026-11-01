<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Post;
use App\Models\PostComment;   // rename to Comment if that is your model's name
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    public function index(Request $request, Post $post)
    {
        abort_unless($post->canBeSeenBy($request->user()), 403);

        $comments = $post->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->latest()
            ->get();

        $this->markDeletable($comments, $post, $request->user()->id);

        return [
            'data' => CommentResource::collection($comments)->resolve(),
            'count' => $post->comments()->count(),
        ];
    }

    public function store(Request $request, Post $post)
    {
        abort_unless($post->canBeSeenBy($request->user()), 403);

        $table = (new PostComment)->getTable();

        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', Rule::exists($table, 'id')->where('post_id', $post->id)],
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ]);

        $comment->load('user');
        $comment->setRelation('replies', collect());
        $comment->setAttribute('can_delete', true);

        return response()->json([
            'comment' => (new CommentResource($comment))->resolve(),
            'count' => $post->comments()->count(),
        ], 201);
    }

    public function destroy(Request $request, PostComment $comment)
    {
        $me = $request->user()->id;
        $post = $comment->post;

        abort_unless($comment->user_id === $me || $post->user_id === $me, 403);

        $comment->replies()->delete();
        $comment->delete();

        return ['count' => $post->comments()->count()];
    }

    private function markDeletable($comments, Post $post, int $me): void
    {
        foreach ($comments as $c) {
            $c->setAttribute('can_delete', $c->user_id === $me || $post->user_id === $me);
            if ($c->relationLoaded('replies')) {
                $this->markDeletable($c->replies, $post, $me);
            }
        }
    }
}

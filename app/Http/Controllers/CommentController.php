<?php

namespace App\Http\Controllers;

use App\Models\PostComment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    /** AJAX: all comments of a post (top-level with replies), already rendered as HTML. */
    public function index(Request $request, Post $post)
    {
        abort_unless($post->canBeSeenBy($request->user()), 403);

        $comments = $post->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->latest()
            ->get();

        return response()->json([
            'count' => $post->comments()->count(),
            'html' => $comments
                ->map(fn ($c) => view('posts._comment', ['comment' => $c, 'post' => $post, 'depth' => 0])->render())
                ->implode(''),
        ]);
    }

    public function store(Request $request, Post $post)
    {
        abort_unless($post->canBeSeenBy($request->user()), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'parent_id' => ['nullable', Rule::exists('comments', 'id')->where('post_id', $post->id)],
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ]);

        if ($request->expectsJson()) {
            $comment->load('user');
            $comment->setRelation('replies', collect());

            return response()->json([
                'count' => $post->comments()->count(),
                'html' => view('posts._comment', [
                    'comment' => $comment,
                    'post' => $post,
                    'depth' => $comment->parent_id ? 1 : 0,
                ])->render(),
            ], 201);
        }

        return back()->with('status', 'Comment added.');
    }

    public function destroy(Request $request, PostComment $comment)
    {
        $userId = $request->user()->id;
        $post = $comment->post;

        // The author or the post owner can remove a comment.
        abort_unless($comment->user_id === $userId || $post->user_id === $userId, 403);

        $comment->replies()->delete();
        $comment->delete();

        if ($request->expectsJson()) {
            return response()->json(['count' => $post->comments()->count()]);
        }

        return back()->with('status', 'Comment deleted.');
    }
}

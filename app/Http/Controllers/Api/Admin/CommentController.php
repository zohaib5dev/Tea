<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\PostComment;   // rename to Comment if that is your model's name
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $comments = PostComment::with(['user:id,name,avatar', 'post:id,body'])
            ->when($request->query('search'), function ($q, $s) {
                $q->where(fn ($w) => $w->where('body', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")));
            })
            ->latest()
            ->paginate(15);

        return [
            'data' => $comments->map(fn ($c) => [
                'id' => $c->id,
                'body' => $c->body,
                'is_reply' => (bool) $c->parent_id,
                'created_at' => $c->created_at->toIso8601String(),
                'user' => ['id' => $c->user->id, 'name' => $c->user->name, 'avatar_url' => UserResource::url($c->user->avatar)],
                'post' => $c->post ? ['id' => $c->post->id, 'snippet' => str($c->post->body ?? 'Photo post')->limit(60)->toString()] : null,
            ])->values(),
            'page' => $comments->currentPage(),
            'last_page' => $comments->lastPage(),
            'total' => $comments->total(),
        ];
    }

    public function destroy(Request $request, PostComment $comment)
    {
        Log::info('admin', ['by' => $request->user()->id, 'action' => 'delete-comment', 'comment' => $comment->id, 'author' => $comment->user_id]);

        $comment->replies()->delete();
        $comment->delete();

        return response()->noContent();
    }
}

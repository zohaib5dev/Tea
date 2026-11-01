<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with('user:id,name,avatar')
            ->withCount(['likes', 'comments'])
            ->when($request->query('search'), function ($q, $s) {
                $q->where(fn ($w) => $w->where('body', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")));
            })
            ->latest()
            ->paginate(15);

        return [
            'data' => $posts->map(fn ($p) => [
                'id' => $p->id,
                'body' => $p->body,
                'visibility' => $p->visibility,
                'media_url' => UserResource::url($p->getAttribute('media') ?? $p->getAttribute('image')),
                'created_at' => $p->created_at->toIso8601String(),
                'likes_count' => $p->likes_count,
                'comments_count' => $p->comments_count,
                'user' => ['id' => $p->user->id, 'name' => $p->user->name, 'avatar_url' => UserResource::url($p->user->avatar)],
            ])->values(),
            'page' => $posts->currentPage(),
            'last_page' => $posts->lastPage(),
            'total' => $posts->total(),
        ];
    }

    public function destroy(Request $request, Post $post)
    {
        Log::info('admin', ['by' => $request->user()->id, 'action' => 'delete-post', 'post' => $post->id, 'author' => $post->user_id]);
        $post->delete();

        return response()->noContent();
    }
}

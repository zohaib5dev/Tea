<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /** Name of the picture column on `posts`. */
    private const MEDIA = 'media';

    public function show(Request $request, User $user)
    {
        $viewer = $request->user();
        $friendship = $viewer->id === $user->id ? null : $viewer->friendshipWith($user);

        return [
            'user' => (new UserResource($user))->resolve(),
            'friendship' => $friendship ? [
                'id' => $friendship->id,
                'status' => $friendship->status,
                'i_sent' => $friendship->sender_id === $viewer->id,
            ] : null,
            'friends_count' => $user->friendIds()->count(),
            'posts_count' => Post::where('user_id', $user->id)->visibleTo($viewer)->count(),
            'photos_count' => Post::where('user_id', $user->id)->whereNotNull(self::MEDIA)->visibleTo($viewer)->count(),
        ];
    }

    public function posts(Request $request, User $user)
    {
        $viewer = $request->user();

        $posts = Post::with('user')
            ->withCount(['likes', 'comments'])
            ->withExists(['likes as liked' => fn ($q) => $q->where('user_id', $viewer->id)])
            ->where('user_id', $user->id)
            ->visibleTo($viewer)
            ->latest()
            ->paginate(10);

        return [
            'data' => PostResource::collection($posts)->resolve(),
            'next_page' => $posts->hasMorePages() ? $posts->currentPage() + 1 : null,
        ];
    }

    public function photos(Request $request, User $user)
    {
        return Post::where('user_id', $user->id)
            ->whereNotNull(self::MEDIA)
            ->visibleTo($request->user())
            ->latest()
            ->limit(60)
            ->get(['id', self::MEDIA])
            ->map(fn ($p) => ['id' => $p->id, 'url' => UserResource::url($p->{self::MEDIA})])
            ->values();
    }
}

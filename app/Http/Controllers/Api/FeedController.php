<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Http\Resources\UserResource;
use App\Models\Post;
use App\Services\SocialData;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $posts = Post::with('user')
            ->withCount(['likes', 'comments'])
            ->withExists(['likes as liked' => fn ($q) => $q->where('user_id', $me->id)])
            ->visibleTo($me)
            ->latest()
            ->paginate(10);

        return [
            'data' => PostResource::collection($posts)->resolve(),
            'next_page' => $posts->hasMorePages() ? $posts->currentPage() + 1 : null,
        ];
    }

    /** Right-hand panel of the feed: friends, requests, suggestions. */
    public function sidebar(Request $request, SocialData $social)
    {
        $me = $request->user();

        return [
            'friends' => UserResource::collection($social->friendsByActivity($me)->take(6)->values())->resolve(),
            'requests' => $me->receivedFriendships()->pending()->with('sender')->latest()->get()
                ->map(fn ($f) => ['id' => $f->id, 'created_at' => $f->created_at->toIso8601String(), 'user' => (new UserResource($f->sender))->resolve()])
                ->values(),
            'suggestions' => UserResource::collection($me->suggestions(5))->resolve(),
        ];
    }
}

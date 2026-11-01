<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __invoke(Request $request, Post $post)
    {
        abort_unless($post->canBeSeenBy($request->user()), 403);

        $userId = $request->user()->id;

        $deleted = $post->likes()->where('user_id', $userId)->forceDelete();

        if ($deleted) {
            $liked = false;
        } else {
            $post->likes()->create(['user_id' => $userId]);   
            $liked = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'count' => $post->likes()->count(),
            ]);
        }

        return back();
    }
}

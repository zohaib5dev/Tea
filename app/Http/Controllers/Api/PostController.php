<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:media'],
            'media' => ['nullable', 'image', 'max:5120'],
            'visibility' => ['nullable', 'in:public,friends,private'],
        ]);

        $post = $request->user()->posts()->create([
            'body' => $data['body'] ?? null,
            'media' => $request->hasFile('media') ? $request->file('media')->store('posts', 'public') : null,
            'visibility' => $data['visibility'] ?? 'friends',
        ]);

        $post->load('user');

        return response()->json((new PostResource($post))->resolve(), 201);
    }

    public function show(Request $request, Post $post)
    {
        abort_unless($post->canBeSeenBy($request->user()), 403);

        $post->load('user')
            ->loadCount(['likes', 'comments'])
            ->loadExists(['likes as liked' => fn ($q) => $q->where('user_id', $request->user()->id)]);

        return (new PostResource($post))->resolve();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:2000', 'required_without:media'],
            'media' => ['nullable', 'max:5120'],
            'visibility' => ['nullable', 'in:public,friends,private'],
        ]);

        $post = $request->user()->posts()->create([
            'body' => $data['body'] ?? null,
            'media' => $request->hasFile('media') ? $request->file('media')->store('posts', 'public') : null,
            'visibility' => $data['visibility'] ?? 'friends',
        ]);

        // AJAX: return the finished post card so the page can insert it without reloading.
        if ($request->expectsJson()) {
            $post->load('user');
            $post->setAttribute('likes_count', 0);
            $post->setAttribute('comments_count', 0);
            $post->setAttribute('liked', false);

            return response()->json([
                'html' => view('partials.post-card', ['post' => $post])->render(),
            ], 201);
        }

        return redirect()->route('feed')->with('status', 'Posted.');
    }

    /** Single post page. Comments load on the page through AJAX. */
    public function show(Request $request, Post $post)
    {
        abort_unless($post->canBeSeenBy($request->user()), 403);

        $post->load('user')
            ->loadCount(['likes', 'comments'])
            ->loadExists(['likes as liked' => fn($q) => $q->where('user_id', $request->user()->id)]);

        return view('posts.show', compact('post'));
    }



  public function update(Request $request, Post $post)
{
    abort_unless($request->user()->id === $post->user_id, 403, 'You are not allowed to edit this post.');

    $data = $request->validate([
        'body' => ['nullable', 'string', 'max:5000'],
        'media' => ['nullable', 'image', 'max:4096'],
        'remove_media' => ['nullable', 'boolean'],
        'visibility' => ['nullable', 'in:public,friends,private'],
    ]);

    $body = trim($data['body'] ?? '');
    $newFile = $request->file('media');
    $removing = $request->boolean('remove_media');
 
    if ($body === '' && ! $newFile && (! $post->media || $removing)) {
        return response()->json(['message' => 'A post needs some text or a photo.'], 422);
    }

    $oldMedia = $post->media;

    if ($newFile) {
        $post->media = $newFile->store('posts', 'public');
    } elseif ($removing) {
        $post->media = null;
    }

    $post->body = $body === '' ? null : $body;
    $post->visibility = $data['visibility'] ?? $post->visibility;
    $post->save();
 
    if ($oldMedia && $oldMedia !== $post->media) {
        Storage::disk('public')->delete($oldMedia);
    }

    return response()->json([
        'message' => 'Post updated.',
        'id' => $post->id,
        'body' => $post->body,
        'media_url' => $post->media ? asset('storage/' . $post->media) : null,
        'visibility' => $post->visibility,
    ]);
}

    public function destroy(Request $request, Post $post)
    {
        abort_unless($post->user_id === $request->user()->id, 403);

        $post->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('feed')->with('status', 'Post deleted.');
    }
}

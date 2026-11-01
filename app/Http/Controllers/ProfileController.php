<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    private const IMAGE_COLUMN = 'media';

    public function show(Request $request, User $user)
    {
        $viewer = $request->user();

        $posts = Post::with('user')
            ->withCount(['likes', 'comments'])
            ->withExists(['likes as liked' => fn($q) => $q->where('user_id', $viewer->id)])
            ->where('user_id', $user->id)
            ->visibleTo($viewer)
            ->latest()
            ->paginate(10);

        // Pictures from this person's posts that the viewer is allowed to see.
        $photoQuery = Post::where('user_id', $user->id)
            ->whereNotNull(self::IMAGE_COLUMN)
            ->visibleTo($viewer);

        $photos = (clone $photoQuery)->latest()->limit(9)->get(['id', self::IMAGE_COLUMN])
            ->map(fn($p) => ['id' => $p->id, 'url' => asset('storage/' . $p->{self::IMAGE_COLUMN})]);

        return view('profile.show', [
            'user' => $user,
            'posts' => $posts,
            'photos' => $photos,
            'photosCount' => (clone $photoQuery)->count(),
            'friendship' => $viewer->id === $user->id ? null : $viewer->friendshipWith($user),
            'friendsCount' => $user->friendIds()->count(),
        ]);
    }

    /** AJAX: change your name and bio. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:255'],
        ]);

        $request->user()->forceFill($data)->save();

        if ($request->expectsJson()) {
            return response()->json(['name' => $data['name'], 'bio' => $data['bio'] ?? '']);
        }

        return back()->with('status', 'Profile updated.');
    }

    /** AJAX: upload a profile picture (type=avatar) or a cover photo (type=cover). */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:avatar,cover'],
            'photo' => ['required', 'image', 'max:4096'],   // 4 MB
        ]);

        $user = $request->user();
        $isAvatar = $request->input('type') === 'avatar';
        $column = $isAvatar ? 'avatar' : 'cover_photo';

        $path = $request->file('photo')->store($isAvatar ? 'avatars' : 'covers', 'public');

        // Remove the old file so storage does not fill up with unused pictures.
        if ($user->{$column}) {
            Storage::disk('public')->delete($user->{$column});
        }

        $user->forceFill([$column => $path])->save();

        if ($request->expectsJson()) {
            return response()->json(['url' => asset('storage/' . $path)]);
        }

        return back()->with('status', 'Photo updated.');
    }
}

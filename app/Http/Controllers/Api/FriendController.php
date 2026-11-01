<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\SocialData;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index(Request $request, SocialData $social)
    {
        $me = $request->user();

        $pair = fn ($f, $other) => [
            'id' => $f->id,
            'created_at' => $f->created_at->toIso8601String(),
            'user' => (new UserResource($other))->resolve(),
        ];

        return [
            'friends' => UserResource::collection($social->friendsByActivity($me))->resolve(),
            'incoming' => $me->receivedFriendships()->pending()->with('sender')->latest()->get()->map(fn ($f) => $pair($f, $f->sender))->values(),
            'outgoing' => $me->sentFriendships()->pending()->with('recipient')->latest()->get()->map(fn ($f) => $pair($f, $f->recipient))->values(),
            'suggestions' => UserResource::collection($me->suggestions(12))->resolve(),
        ];
    }
}

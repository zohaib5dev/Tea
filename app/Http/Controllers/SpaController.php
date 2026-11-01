<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpaController extends Controller
{
    /** Serves the Vue application for every non-API URL. */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user && $user->banned_at) {   // suspended: sign out
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $user = null;
        }

        return view('spa', [
            'user' => $user ? (new UserResource($user))->resolve() : null,
            'ice' => config('services.webrtc.ice_servers', [['urls' => 'stun:stun.l.google.com:19302']]),
        ]);
    }
}

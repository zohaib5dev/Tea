<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** A suspended account is logged out on its next request. */
class EnsureNotBanned
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->banned_at) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json(['message' => 'Your account has been suspended.', 'banned' => true], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Marks the logged-in user as "online" by keeping users.last_seen_at fresh.
 * Anyone seen in the last 5 minutes counts as online (see User::getIsOnlineAttribute).
 */
class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $last = $user->last_seen_at ? Carbon::parse($user->last_seen_at) : null;

            // Write at most once a minute to keep database load low.
            if (! $last || $last->lt(now()->subMinute())) {
                $user->forceFill(['last_seen_at' => now()])->saveQuietly();
            }
        }

        return $next($request);
    }
}
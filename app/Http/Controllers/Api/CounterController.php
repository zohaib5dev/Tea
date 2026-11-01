<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CounterController extends Controller
{
    /** Numbers for the top bar badges. */
    public function __invoke(Request $request)
    {
        $me = $request->user();

        return ['pending_requests' => $me->receivedFriendships()->pending()->count()];
    }
}

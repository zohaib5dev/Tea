<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\Post;
use App\Models\PostComment;   // rename to Comment if that is your model's name
use App\Models\User;

class DashboardController extends Controller
{
    private const DAYS = 14;

    public function __invoke()
    {
        $today = now()->startOfDay();
        $week = now()->subDays(7);

        $calls = Message::where('type', 'call');

        return [
            'users' => [
                'total' => User::count(),
                'today' => User::where('created_at', '>=', $today)->count(),
                'week' => User::where('created_at', '>=', $week)->count(),
                'online' => User::where('last_seen_at', '>=', now()->subMinutes(5))->count(),
                'banned' => User::whereNotNull('banned_at')->count(),
                'admins' => User::where('is_admin', true)->count(),
            ],
            'posts' => ['total' => Post::count(), 'today' => Post::where('created_at', '>=', $today)->count()],
            'comments' => ['total' => PostComment::count(), 'today' => PostComment::where('created_at', '>=', $today)->count()],
            'messages' => [
                'total' => Message::where('type', '!=', 'call')->count(),
                'today' => Message::where('type', '!=', 'call')->where('created_at', '>=', $today)->count(),
            ],
            'calls' => [
                'total' => (clone $calls)->count(),
                'missed' => (clone $calls)->where('call_status', 'missed')->count(),
                'minutes' => (int) round(((clone $calls)->sum('call_duration')) / 60),
            ],
            'friendships' => Friendship::where('status', 'accepted')->count(),
            'series' => [
                'users' => $this->series(User::query()),
                'posts' => $this->series(Post::query()),
                'messages' => $this->series(Message::query()->where('type', '!=', 'call')),
            ],
            'recent_users' => AdminUserResource::collection(User::latest()->limit(6)->get())->resolve(),
        ];
    }

    /** Rows per day for the last 14 days (days without rows count as 0). */
    private function series($query): array
    {
        $from = now()->subDays(self::DAYS - 1)->startOfDay();

        $rows = $query->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as d, count(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $out = [];
        for ($i = 0; $i < self::DAYS; $i++) {
            $date = $from->copy()->addDays($i)->toDateString();
            $out[] = ['date' => $date, 'count' => (int) ($rows[$date] ?? 0)];
        }

        return $out;
    }
}

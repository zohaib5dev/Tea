<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->withCount('posts')
            ->when($request->query('search'), function ($q, $s) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
            })
            ->when($request->query('filter') === 'admins', fn ($q) => $q->where('is_admin', true))
            ->when($request->query('filter') === 'banned', fn ($q) => $q->whereNotNull('banned_at'))
            ->when($request->query('filter') === 'online', fn ($q) => $q->where('last_seen_at', '>=', now()->subMinutes(5)))
            ->latest()
            ->paginate(15);

        return [
            'data' => AdminUserResource::collection($users)->resolve(),
            'page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'total' => $users->total(),
        ];
    }

    public function ban(Request $request, User $user)
    {
        $this->guard($request, $user);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $user->forceFill(['banned_at' => now(), 'ban_reason' => $data['reason'] ?? null])->save();

        // Log them out everywhere right away (when sessions are stored in the database).
        if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        $this->log($request, 'ban', $user, $data['reason'] ?? null);

        return (new AdminUserResource($user))->resolve();
    }

    public function unban(Request $request, User $user)
    {
        $user->forceFill(['banned_at' => null, 'ban_reason' => null])->save();
        $this->log($request, 'unban', $user);

        return (new AdminUserResource($user))->resolve();
    }

    public function setAdmin(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot change your own admin rights.');

        $value = $request->boolean('value');
        $user->forceFill(['is_admin' => $value])->save();
        $this->log($request, $value ? 'make-admin' : 'remove-admin', $user);

        return (new AdminUserResource($user))->resolve();
    }

    public function destroy(Request $request, User $user)
    {
        $this->guard($request, $user);

        foreach (['avatar', 'cover_photo'] as $column) {
            if ($user->getAttribute($column)) {
                Storage::disk('public')->delete($user->getAttribute($column));
            }
        }

        $this->log($request, 'delete', $user);
        $user->delete();   // posts, comments, messages and friendships follow through the foreign keys

        return response()->noContent();
    }

    /** Admins cannot act on themselves or on other admins (remove their admin rights first). */
    private function guard(Request $request, User $target): void
    {
        abort_if($target->id === $request->user()->id, 422, 'You cannot do this to your own account.');
        abort_if($target->is_admin, 422, 'Remove admin rights first.');
    }

    private function log(Request $request, string $action, User $target, ?string $note = null): void
    {
        Log::info('admin', ['by' => $request->user()->id, 'action' => $action, 'user' => $target->id, 'note' => $note]);
    }
}

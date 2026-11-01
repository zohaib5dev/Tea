<?php

namespace App\Models\Concerns;

use App\Models\PostComment;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Add to your User model:  use HasSocialFeatures;
 * Also add 'avatar', 'cover_photo', 'bio' to $fillable if you want mass-assignment.
 */
trait HasSocialFeatures
{
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    public function sentFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'sender_id');
    }

    public function receivedFriendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'recipient_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['role', 'last_read_at', 'is_muted'])
            ->withTimestamps();
    }

    /** Online if seen in the last 5 minutes. Used as $user->is_online in Blade. */
    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gte(now()->subMinutes(5));
    }

    /** IDs of all accepted friends. */
    public function friendIds()
    {
        return Friendship::accepted()
            ->where(fn($q) => $q->where('sender_id', $this->id)->orWhere('recipient_id', $this->id))
            ->get(['sender_id', 'recipient_id'])
            ->map(fn($f) => $f->sender_id === $this->id ? $f->recipient_id : $f->sender_id);
    }

    /** Query builder of friends, so you can chain ->limit(), ->orderBy(), etc. */
    public function friends()
    {
        return User::whereIn('id', $this->friendIds());
    }

    public function isFriendsWith(User $other): bool
    {
        return Friendship::accepted()->between($this->id, $other->id)->exists();
    }

    public function friendshipWith(User $other): ?Friendship
    {
        return Friendship::between($this->id, $other->id)->first();
    }

    /**
     * People you may know: users with no relationship to you yet,
     * with mutual_friends_count attached, most mutual friends first.
     */

    public function suggestions(int $limit = 5)
    {
        $excluded = Friendship::where(
            fn($q) =>
            $q->where('sender_id', $this->id)
                ->orWhere('recipient_id', $this->id)
        )
            ->get(['sender_id', 'recipient_id'])
            ->flatMap(fn($f) => [$f->sender_id, $f->recipient_id])
            ->push($this->id)
            ->unique();

        $candidates = User::whereNotIn('id', $excluded)
            ->inRandomOrder()
            ->limit($limit * 3)
            ->get();

        $ids = $candidates->pluck('id');
        $mine = collect($this->friendIds());

        $edges = Friendship::accepted()
            ->where(
                fn($q) =>
                $q->whereIn('sender_id', $ids)
                    ->orWhereIn('recipient_id', $ids)
            )
            ->get(['sender_id', 'recipient_id']);

        foreach ($candidates as $candidate) {
            $theirs = $edges
                ->filter(
                    fn($e) =>
                    $e->sender_id === $candidate->id ||
                        $e->recipient_id === $candidate->id
                )
                ->map(
                    fn($e) =>
                    $e->sender_id === $candidate->id
                        ? $e->recipient_id
                        : $e->sender_id
                )
                ->values()
                ->toBase();

            $candidate->mutual_friends_count = $theirs
                ->intersect($mine)
                ->count();
        }

        return $candidates
            ->sortByDesc('mutual_friends_count')
            ->take($limit)
            ->values();
    }
}

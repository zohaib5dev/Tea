<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = ['user_id', 'body', 'media', 'visibility'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    /** Posts the given user is allowed to see: own, public, and friends' "friends" posts. */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        $friendIds = $viewer->friendIds();

        return $query->where(function ($q) use ($viewer, $friendIds) {
            $q->where('user_id', $viewer->id)
                ->orWhere('visibility', 'public')
                ->orWhere(fn ($q) => $q->where('visibility', 'friends')->whereIn('user_id', $friendIds));
        });
    }

    public function canBeSeenBy(User $viewer): bool
    {
        return $this->user_id === $viewer->id
            || $this->visibility === 'public'
            || ($this->visibility === 'friends' && $viewer->isFriendsWith($this->user));
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    use HasUuid;

    protected $fillable = ['sender_id', 'recipient_id', 'status', 'accepted_at'];

    protected $casts = ['accepted_at' => 'datetime'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /** The relationship between two users, whichever of them sent the request. */
    public function scopeBetween(Builder $query, int $a, int $b): Builder
    {
        return $query->where(function ($q) use ($a, $b) {
            $q->where(fn ($q) => $q->where('sender_id', $a)->where('recipient_id', $b))
                ->orWhere(fn ($q) => $q->where('sender_id', $b)->where('recipient_id', $a));
        });
    }
}

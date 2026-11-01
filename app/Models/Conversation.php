<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Conversation extends Model
{
    use HasUuid;

    protected $fillable = ['type', 'title', 'avatar', 'created_by', 'last_message_at'];

    protected $casts = ['last_message_at' => 'datetime'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'last_read_at', 'is_muted', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('users.id', $user->id)->exists();
    }

    /** Title shown in the inbox: group title, or the other person's name. */
    public function displayTitleFor(User $viewer): string
    {
        if ($this->type === 'group') {
            return $this->title ?: 'Group chat';
        }

        return $this->participants->firstWhere('id', '!=', $viewer->id)?->name ?? 'Conversation';
    }

    /** Reuse the existing 1-to-1 chat between two users, or create it. */
    public static function findOrCreatePrivate(User $a, User $b): self
    {
        $existing = static::where('type', 'private')
            ->whereHas('participants', fn ($q) => $q->where('users.id', $a->id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $b->id))
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($a, $b) {
            $conversation = static::create(['type' => 'private', 'created_by' => $a->id]);
            $conversation->participants()->attach([$a->id, $b->id]);

            return $conversation;
        });
    }
}

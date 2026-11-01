<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Message extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'conversation_id', 'user_id', 'reply_to_id', 'body',
        'attachment_path', 'attachment_type', 'edited_at',
        'type', 'call_id', 'call_kind', 'call_status', 'call_connected_at', 'call_duration',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'call_connected_at' => 'datetime',
        'call_duration' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    /** Messages this user has not hidden with "Delete for me". */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereNotExists(function ($sub) use ($user) {
            $sub->select(DB::raw(1))
                ->from('message_deletions')
                ->whereColumn('message_deletions.message_id', 'messages.id')
                ->where('message_deletions.user_id', $user->id);
        });
    }

    /** One line for the sidebar and notifications: "Hello", "Sent a photo", "Voice call, 2:31", "Missed video call". */
    public function previewText(): string
    {
        if ($this->type === 'call') {
            return $this->callLabel();
        }

        return $this->body ?: ($this->attachment_type === 'image' ? 'Sent a photo' : 'Sent an attachment');
    }

    public function callLabel(): string
    {
        $kind = $this->call_kind === 'video' ? 'Video call' : 'Voice call';

        return match ($this->call_status) {
            'ended' => $this->call_duration ? $kind.', '.self::clock($this->call_duration) : $kind.' ended',
            'missed' => 'Missed '.strtolower($kind),
            'declined' => 'Call declined',
            default => $kind,
        };
    }

    /** 75 becomes "1:15", 3725 becomes "1:02:05". */
    public static function clock(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return $h ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }

    /** Calls that were never closed properly (a browser was closed mid-call) are finished here. */
    public static function finalizeStaleCalls(int $conversationId): void
    {
        static::where('conversation_id', $conversationId)->where('type', 'call')
            ->where('call_status', 'ringing')->where('created_at', '<', now()->subSeconds(90))
            ->update(['call_status' => 'missed']);

        static::where('conversation_id', $conversationId)->where('type', 'call')
            ->where('call_status', 'ongoing')->where('updated_at', '<', now()->subHours(6))
            ->update(['call_status' => 'ended']);
    }
}

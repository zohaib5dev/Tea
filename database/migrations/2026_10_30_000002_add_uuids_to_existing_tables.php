<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TABLE_KEYS = [
        'users' => ['id'],
        'password_reset_tokens' => ['email'],
        'sessions' => ['id'],
        'cache' => ['key'],
        'cache_locks' => ['key'],
        'jobs' => ['id'],
        'job_batches' => ['id'],
        'failed_jobs' => ['id'],
        'posts' => ['id'],
        'post_reactions' => ['post_id', 'user_id'],
        'post_comments' => ['id'],
        'friendships' => ['id'],
        'conversations' => ['id'],
        'conversation_participants' => ['id'],
        'messages' => ['id'],
        'message_deletions' => ['id'],
    ];

    public function up(): void
    {
        foreach (self::TABLE_KEYS as $tableName => $primaryKeys) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'uuid')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->uuid('uuid')->nullable();
                });
            }

            do {
                $rows = DB::table($tableName)->whereNull('uuid')->limit(500)->get($primaryKeys);

                foreach ($rows as $row) {
                    $keys = [];
                    foreach ($primaryKeys as $primaryKey) {
                        $keys[$primaryKey] = $row->{$primaryKey};
                    }

                    DB::table($tableName)->where($keys)->update(['uuid' => (string) Str::uuid()]);
                }
            } while ($rows->isNotEmpty());

            if (! Schema::hasIndex($tableName, ['uuid'], 'unique')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->unique('uuid');
                });
            }
        }
    }

    public function down(): void
    {
        // UUIDs are retained because removing them would discard generated identifiers.
    }
};

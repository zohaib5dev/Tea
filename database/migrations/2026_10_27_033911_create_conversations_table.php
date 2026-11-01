<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('type', ['private', 'group'])->default('private');
            $table->string('title')->nullable();   // group name
            $table->string('avatar')->nullable();  // group picture
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index(); // inbox ordering
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['member', 'admin'])->default('member');
            $table->timestamp('last_read_at')->nullable(); // unread = messages newer than this
            $table->boolean('is_muted')->default(false);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // sender
            $table->foreignId('reply_to_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('attachment_type', ['image', 'video', 'audio', 'file'])->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // "delete for everyone"

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

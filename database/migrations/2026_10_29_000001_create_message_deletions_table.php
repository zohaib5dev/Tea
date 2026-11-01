<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Delete for me": hides one message from one user only.
        Schema::create('message_deletions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_deletions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A call is stored as a special message so it shows up in the chat like in Messenger.
        Schema::table('messages', function (Blueprint $table) {
            $table->string('type', 20)->default('text')->after('user_id');   // 'text' or 'call'
            $table->string('call_id', 64)->nullable()->index();
            $table->string('call_kind', 10)->nullable();                     // voice | video
            $table->string('call_status', 12)->nullable();                   // ringing | ongoing | ended | missed | declined
            $table->timestamp('call_connected_at')->nullable();
            $table->unsignedInteger('call_duration')->nullable();            // seconds
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'call_id', 'call_kind', 'call_status', 'call_connected_at', 'call_duration']);
        });
    }
};

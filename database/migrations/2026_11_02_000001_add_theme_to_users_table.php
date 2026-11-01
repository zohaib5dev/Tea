<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The two colors a user picked. NULL means "use the default gold theme".
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme_accent', 7)->nullable();       // e.g. #d4a63a
            $table->string('theme_background', 7)->nullable();   // e.g. #0f0c07
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme_accent', 'theme_background']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            // Check if the column exists before trying to add it
            if (!Schema::hasColumn('chats', 'meta')) {
                $table->json('meta')->nullable()->after('receiver_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            // Check if the column exists before trying to drop it
            if (Schema::hasColumn('chats', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};
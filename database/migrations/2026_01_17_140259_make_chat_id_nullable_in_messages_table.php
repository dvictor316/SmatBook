<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * domain => env('SESSION_DOMAIN', null)
     * Handles Chat and Message for emails logic (2026-01-17 constraint).
     */
    public function up(): void
    {
        // Disable foreign key checks to prevent the 'Incompatible' error
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Check if column exists before trying to modify it
        if (!Schema::hasColumn('messages', 'chat_id')) {
            Schema::table('messages', function (Blueprint $table) {
                // Create it if it doesn't exist (Fixes your SQLSTATE[42S22] error)
                $table->string('chat_id', 191)->nullable()->after('id');
            });
        } else {
            // 2. If it exists, manually drop the constraint if present
            try {
                DB::statement('ALTER TABLE messages DROP FOREIGN KEY messages_chat_id_foreign');
            } catch (\Exception $e) {
                // Ignore if constraint doesn't exist
            }

            // 3. Force change the column to VARCHAR(191) to hold the UUID/String IDs
            DB::statement('ALTER TABLE messages MODIFY chat_id VARCHAR(191) NULL');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        // Reverting usually involves changing back to BIGINT if necessary
    }
};
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
        if (! Schema::hasTable('messages')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        if (! Schema::hasColumn('messages', 'chat_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('chat_id', 191)->nullable()->after('id');
            });
        } else {
            if (DB::getDriverName() === 'mysql') {
                try {
                    DB::statement('ALTER TABLE messages DROP FOREIGN KEY messages_chat_id_foreign');
                } catch (\Throwable) {
                    // The column may never have had a foreign key.
                }
            }

            Schema::table('messages', function (Blueprint $table) {
                $table->string('chat_id', 191)->nullable()->change();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Reverting usually involves changing back to BIGINT if necessary
    }
};

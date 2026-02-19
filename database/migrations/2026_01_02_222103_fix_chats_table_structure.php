<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('chats', function (Blueprint $table) {
        // Remove the old columns causing the error
        if (Schema::hasColumn('chats', 'sender_id')) {
            $table->dropColumn('sender_id');
        }
        if (Schema::hasColumn('chats', 'message')) {
            $table->dropColumn('message');
        }
        if (Schema::hasColumn('chats', 'sender_name')) {
            $table->dropColumn('sender_name');
        }

        // Add the new columns if they don't exist yet
        if (!Schema::hasColumn('chats', 'user_id')) {
            $table->unsignedBigInteger('user_id')->after('id');
        }
        if (!Schema::hasColumn('chats', 'content')) {
            $table->text('content')->after('receiver_id');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            //
        });
    }
};

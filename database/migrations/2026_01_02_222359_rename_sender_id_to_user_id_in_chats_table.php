<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
public function up()
{
    Schema::table('chats', function (Blueprint $table) {
        // Rename sender_id to user_id
        if (Schema::hasColumn('chats', 'sender_id')) {
            $table->renameColumn('sender_id', 'user_id');
        }
        // Rename message to content
        if (Schema::hasColumn('chats', 'message')) {
            $table->renameColumn('message', 'content');
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

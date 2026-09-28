<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chats')) {
            return;
        }

        foreach (['sender_id', 'message', 'sender_name'] as $column) {
            if (Schema::hasColumn('chats', $column)) {
                Schema::table('chats', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }

        Schema::table('chats', function (Blueprint $table) {
            if (! Schema::hasColumn('chats', 'user_id')) {
                $table->unsignedBigInteger('user_id')->after('id');
            }
            if (! Schema::hasColumn('chats', 'content')) {
                $table->text('content')->after('receiver_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('chats')) {
            return;
        }

        Schema::table('chats', function (Blueprint $table) {
            if (! Schema::hasColumn('chats', 'sender_id')) {
                $table->unsignedBigInteger('sender_id')->after('id');
            }
            if (! Schema::hasColumn('chats', 'message')) {
                $table->text('message')->after('receiver_id');
            }
            if (! Schema::hasColumn('chats', 'sender_name')) {
                $table->string('sender_name')->nullable();
            }
        });

        foreach (['user_id', 'content'] as $column) {
            if (Schema::hasColumn('chats', $column)) {
                Schema::table('chats', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};

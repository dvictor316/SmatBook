<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // 1. Check for sender_id
            if (!Schema::hasColumn('messages', 'sender_id')) {
                $table->foreignId('sender_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            } else {
                $table->foreignId('sender_id')->nullable()->change();
            }

            // 2. While we are here, let's ensure receiver_id exists for your Email/Chat flow
            if (!Schema::hasColumn('messages', 'receiver_id')) {
                $table->foreignId('receiver_id')->nullable()->after('sender_id')->constrained('users')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropForeign(['receiver_id']);
            $table->dropColumn(['sender_id', 'receiver_id']);
        });
    }
};
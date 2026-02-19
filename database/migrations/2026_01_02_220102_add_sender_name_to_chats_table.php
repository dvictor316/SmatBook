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
            // Wrapping in hasColumn check to resolve the 'Duplicate column name' error
            if (!Schema::hasColumn('chats', 'sender_name')) {
                $table->string('sender_name')->after('receiver_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            if (Schema::hasColumn('chats', 'sender_name')) {
                $table->dropColumn('sender_name');
            }
        });
    }
};
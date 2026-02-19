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
            // We use if(!Schema::hasColumn) to prevent "Duplicate column" errors 
            // if the migration partially ran before.
            if (!Schema::hasColumn('chats', 'sender_id')) {
                $table->unsignedBigInteger('sender_id')->after('id')->index();
            }

            if (!Schema::hasColumn('chats', 'receiver_id')) {
                $table->unsignedBigInteger('receiver_id')->after('sender_id')->index();
            }
            
            // Note: If you want to link these to the users table, 
            // uncomment the lines below after ensuring the 'users' table exists.
            // $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            // These checks solve your "1091 Can't DROP 'sender_id'" error 
            // by verifying existence before attempting deletion.
            if (Schema::hasColumn('chats', 'sender_id')) {
                $table->dropColumn('sender_id');
            }
            
            if (Schema::hasColumn('chats', 'receiver_id')) {
                $table->dropColumn('receiver_id');
            }
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('messages', function (Blueprint $table) {
        // Add user_id (sender) if missing
        if (!Schema::hasColumn('messages', 'user_id')) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users');
        }
        
        // Add receiver_id if missing
        if (!Schema::hasColumn('messages', 'receiver_id')) {
            $table->foreignId('receiver_id')->nullable()->after('user_id')->constrained('users');
        }

        // Add subject if missing
        if (!Schema::hasColumn('messages', 'subject')) {
            $table->string('subject')->nullable()->after('receiver_id');
        }
        
        // Add read_at if missing
        if (!Schema::hasColumn('messages', 'read_at')) {
            $table->timestamp('read_at')->nullable()->after('content');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

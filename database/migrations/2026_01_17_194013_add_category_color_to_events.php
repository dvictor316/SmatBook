<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add missing columns to the events table.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Check if column doesn't exist before adding to avoid errors
            if (!Schema::hasColumn('events', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade')->after('id');
            }
            
            if (!Schema::hasColumn('events', 'category_color')) {
                $table->string('category_color')->default('bg-primary')->after('end');
            }
            
            // Ensure title, start, and end exist if this is a fresh table adjustment
            // If the table is totally empty, you might prefer Schema::create
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'category_color']);
        });
    }
};
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
        // Wrap in a check to prevent the "ColumnDoesNotExist" error 
        // if the column was already renamed during a partial migration.
        if (Schema::hasColumn('domains', 'name')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->renameColumn('name', 'domain');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('domains', 'domain')) {
            Schema::table('domains', function (Blueprint $table) {
                $table->renameColumn('domain', 'name');
            });
        }
    }
};
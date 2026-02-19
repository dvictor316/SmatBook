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
        Schema::table('invoices', function (Blueprint $table) {
            // FIX: Removed the failing ->after('some_other_column').
            // Added ->default(0.00) to ensure existing rows don't cause a NOT NULL error.
            $table->decimal('total', 10, 2)->notNull()->default(0.00); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // This is crucial: You must explicitly drop the column you added in 'up'.
            $table->dropColumn('total');
        });
    }
};
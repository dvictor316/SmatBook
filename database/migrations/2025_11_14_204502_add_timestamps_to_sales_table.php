<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            // The timestamps are already added in the create_sales_table migration.
            // Commenting this out to avoid a "Duplicate column name" error.
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Since we didn't add them in up(), we don't need to drop them here in this specific file's context.
            // $table->dropTimestamps(); 
        });
    }
};

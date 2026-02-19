<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('sale_items', function (Blueprint $table) {
        $table->decimal('discount', 5, 2)->default(0); // Add discount column with decimal type (e.g., 10% = 10.00)
    });
}

public function down()
{
    Schema::table('sale_items', function (Blueprint $table) {
        $table->dropColumn('discount'); // Remove the discount column if rolling back
    });
}

};

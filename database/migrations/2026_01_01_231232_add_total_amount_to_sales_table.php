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
    Schema::table('sales', function (Blueprint $table) {
        $table->decimal('total_amount', 15, 2)->default(0)->after('customer_name');
        $table->decimal('tax_amount', 15, 2)->default(0)->after('total_amount');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            //
        });
    }
};

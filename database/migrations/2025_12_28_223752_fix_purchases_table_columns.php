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
    Schema::table('purchases', function (Blueprint $table) {
        // 1. Rename customer_id to vendor_id if you accidentally named it customer_id
        if (Schema::hasColumn('purchases', 'customer_id')) {
            $table->renameColumn('customer_id', 'vendor_id');
        }

        // 2. Add purchase_no if it doesn't exist
        if (!Schema::hasColumn('purchases', 'purchase_no')) {
            $table->string('purchase_no')->unique()->after('id')->nullable();
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

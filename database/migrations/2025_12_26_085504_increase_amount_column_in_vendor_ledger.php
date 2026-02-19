<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('vendor_ledger_transactions', function (Blueprint $table) {
        // Change to 15 digits total, 2 after the decimal point
        $table->decimal('amount', 15, 2)->change();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_ledger', function (Blueprint $table) {
            //
        });
    }
};

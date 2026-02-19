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
        Schema::table('payments', function (Blueprint $table) {
            // 1. Add the account_id column
            // We place it after 'payment_id' for better table organization
            $table->unsignedBigInteger('account_id')->nullable()->after('payment_id');

            // 2. Set up the Foreign Key relationship
            $table->foreign('account_id')
                  ->references('id')
                  ->on('accounts')
                  ->onDelete('restrict'); // Prevents deleting an account if payments exist

            // 3. Optional: Add an index for faster ledger queries
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // 1. Drop the foreign key first (Required by MySQL)
            $table->dropForeign(['account_id']);
            
            // 2. Drop the column
            $table->dropColumn('account_id');
        });
    }
};
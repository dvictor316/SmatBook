<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <--- 🛑 YOU NEED TO ADD THIS USE STATEMENT!

class AddPlanIdForeignKeyToOrdersTable extends Migration
{
  public function up(): void
{
    Schema::table('orders', function (Blueprint $table) {
        
        // 1. Add the column (only if it doesn't already exist)
        if (!Schema::hasColumn('orders', 'plan_id')) {
            $table->unsignedBigInteger('plan_id')->nullable()->after('id'); 
        }
        
        // 2. Add the foreign key constraint
        // This is safe because the column is nullable, preventing data violations.
        $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
    });
}

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['plan_id']); 
            $table->dropColumn('plan_id');
        });
    }
}
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
    Schema::table('plans', function (Blueprint $table) {
        $table->decimal('price', 10, 2)->after('name')->default(0.00);
        $table->string('billing_cycle')->after('price')->default('monthly'); // monthly, yearly
        $table->text('description')->after('billing_cycle')->nullable();
        $table->json('features')->after('description')->nullable(); // Stores the list of what's included
        $table->boolean('recommended')->after('features')->default(false);
        $table->string('icon')->after('recommended')->nullable();
        // Change status to boolean for easier check
        $table->boolean('is_active')->after('status')->default(true); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            //
        });
    }
};

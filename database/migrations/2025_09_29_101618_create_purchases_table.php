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
    Schema::create('purchases', function (Blueprint $table) {
        $table->id();
        $table->string('purchase_no')->unique(); // e.g., PUR-1001
        $table->unsignedBigInteger('supplier_id')->nullable();
        $table->decimal('total_amount', 15, 2)->default(0);
        $table->string('status')->default('received'); // received, pending, returned
        $table->timestamps();

        $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};

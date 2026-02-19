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
    if (!Schema::hasTable('quotations')) {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_id')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->string('status')->default('Pending');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};

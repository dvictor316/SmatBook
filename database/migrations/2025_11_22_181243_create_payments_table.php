<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Force delete the table if it exists to resolve the '1050' error
        Schema::dropIfExists('payments');

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Matching the schema expected by your SalesController
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('method');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
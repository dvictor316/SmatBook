<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Invoice Payment"
            $table->string('reference');
            $table->string('mode'); // e.g., "Cash", "Bank Transfer", "Invoice"
            $table->decimal('amount', 10, 2); // The transaction amount (positive or negative)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_ledger_transactions');
    }
};

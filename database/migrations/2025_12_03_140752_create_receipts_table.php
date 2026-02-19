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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            
            // Financial Data
            $table->decimal('amount', 10, 2)->default(0.00); 
            
            // Relationships & Metadata
            // Ensures the customer exists and sets the ID to NULL if the customer is deleted
            $table->foreignId('customer_id')
                  ->nullable()
                  ->constrained('customers') // Assumes your customer table is 'customers'
                  ->onDelete('set null');
                  
            $table->string('reference_number')->nullable();
            $table->date('receipt_date');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations (used for php artisan migrate:rollback).
     */
    public function down(): void
    {
        // This is necessary to safely remove the table during a rollback.
        Schema::dropIfExists('receipts');
    }
};
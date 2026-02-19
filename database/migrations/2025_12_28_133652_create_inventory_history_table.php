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
        Schema::create('inventory_history', function (Blueprint $table) {
            $table->id();
            
            // Required for the Multitenantable trait logic
            $table->unsignedBigInteger('user_id')->index(); 
            
            // Product Link
            $table->unsignedBigInteger('product_id'); 
            
            // Use decimal (15,2) to match product pricing/unit precision
            $table->decimal('quantity', 15, 2);
            
            // 'in' (restock), 'out' (sale/breakdown), 'adjustment'
            $table->string('type'); 
            
            // THE FIX: Added 'reference' to store "Initial Stock", "Sale #", or "Broken Carton"
            $table->string('reference', 191)->nullable(); 
            
            $table->text('remarks')->nullable();
            $table->timestamps(); 

            // Foreign key to ensure data integrity
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
                  
            // Indexing for faster history lookups on the domain
            $table->index(['product_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_history');
    }
};
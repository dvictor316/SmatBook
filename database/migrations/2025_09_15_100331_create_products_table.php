<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index(); // Required for Multitenantable trait
            $table->string('name', 191); 
            $table->string('sku', 191)->unique(); 
            $table->string('barcode', 191)->nullable();
            
            // Pricing Logic
            $table->decimal('price', 15, 2); 
            $table->decimal('purchase_price', 15, 2); 
            
            // Inventory Tracking (Dual Columns for parity)
            $table->integer('stock')->default(0); 
            $table->integer('stock_quantity')->default(0); 
            
            // Packaging & Units Schema
            $table->string('base_unit_name', 100)->default('pcs'); // e.g., "kg", "pcs", "meter"
            $table->enum('unit_type', ['unit', 'sachet', 'roll', 'carton'])->default('unit');
            $table->integer('units_per_carton')->default(1);
            $table->integer('units_per_roll')->default(1);
            
            // Metadata & Categorization
            $table->unsignedBigInteger('category_id');
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->string('image')->nullable();
            
            $table->timestamps();

            // Foreign Key Relationships
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            
            // Optional: Indexing for search performance on domain
            $table->index(['name', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
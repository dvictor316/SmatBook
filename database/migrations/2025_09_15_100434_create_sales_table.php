<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            // Identification fields
            $table->string('invoice_no')->unique()->index();
            $table->string('receipt_no')->unique()->index();

            // Customer & user relationships
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'id')->nullOnDelete();
            $table->string('customer_name')->default('Walk-in Customer');
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete(); // cashier/staff
            $table->string('terminal_id')->default('POS1');

            // Financial details
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Payment details
            $table->string('payment_method')->default('cash');
            $table->enum('payment_status', ['paid', 'partial', 'unpaid'])->default('unpaid');
            $table->decimal('paid', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);

            // Optional currency field
            $table->string('currency')->default('NGN');

            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Line item details
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->decimal('subtotal', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};

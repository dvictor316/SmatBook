<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->string('slug', 100)->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->string('store_name');
            $table->text('description')->nullable();
            $table->string('contact_phone', 60)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('fulfilment_note')->nullable();
            $table->boolean('is_enabled')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('online_store_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable();
            $table->string('order_number', 80)->unique();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 60);
            $table->text('delivery_address')->nullable();
            $table->string('payment_method', 40)->default('pay_on_delivery');
            $table->string('status', 40)->default('pending_confirmation')->index();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('customer_note')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
        });

        Schema::create('online_store_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('online_store_order_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_store_order_items');
        Schema::dropIfExists('online_store_orders');
        Schema::dropIfExists('storefront_settings');
    }
};

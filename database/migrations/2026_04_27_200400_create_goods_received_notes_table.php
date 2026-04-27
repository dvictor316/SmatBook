<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Goods Received Notes
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('grn_number', 100)->unique();
            $table->date('received_date');
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->string('status')->default('draft')
                ->comment('draft,received,partially_received,accepted,rejected');
            $table->string('delivery_note_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('received_by_name')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'supplier_id']);
        });

        Schema::create('grn_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grn_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name');
            $table->string('unit')->nullable();
            $table->decimal('ordered_quantity', 18, 4)->default(0);
            $table->decimal('received_quantity', 18, 4);
            $table->decimal('accepted_quantity', 18, 4)->nullable();
            $table->decimal('rejected_quantity', 18, 4)->default(0);
            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->decimal('total_cost', 18, 2)->nullable();
            $table->string('lot_number')->nullable();
            $table->string('serial_numbers')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('grn_id')->references('id')->on('goods_received_notes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grn_items');
        Schema::dropIfExists('goods_received_notes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Purchase Requisitions
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('requisition_number', 100)->unique();
            $table->date('request_date');
            $table->date('required_date')->nullable();
            $table->string('priority')->default('normal')->comment('low,normal,high,urgent');
            $table->string('status')->default('draft')
                ->comment('draft,submitted,approved,rejected,converted,cancelled');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->text('justification')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });

        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_requisition_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('estimated_unit_price', 18, 2)->nullable();
            $table->decimal('estimated_total', 18, 2)->nullable();
            $table->text('specification')->nullable();
            $table->timestamps();

            $table->foreign('purchase_requisition_id')
                ->references('id')->on('purchase_requisitions')->onDelete('cascade');
        });

        // Request for Quotations
        Schema::create('request_for_quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('rfq_number', 100)->unique();
            $table->date('rfq_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft')
                ->comment('draft,sent,received,awarded,cancelled');
            $table->unsignedBigInteger('purchase_requisition_id')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rfq_suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('status')->default('sent')->comment('sent,responded,awarded,rejected');
            $table->date('response_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('rfq_id')->references('id')->on('request_for_quotations')->onDelete('cascade');
        });

        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name');
            $table->string('unit')->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('quoted_price', 18, 2)->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('rfq_id')->references('id')->on('request_for_quotations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('rfq_suppliers');
        Schema::dropIfExists('request_for_quotations');
        Schema::dropIfExists('purchase_requisition_items');
        Schema::dropIfExists('purchase_requisitions');
    }
};

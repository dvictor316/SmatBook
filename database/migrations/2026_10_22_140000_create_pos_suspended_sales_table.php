<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_suspended_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('branch_id', 100)->nullable();
            $table->string('branch_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable();
            $table->string('customer_name')->default('Walk-in Customer');
            $table->json('items');
            $table->unsignedSmallInteger('item_count');
            $table->decimal('total', 15, 2);
            $table->uuid('claim_token')->nullable();
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'created_at'], 'pos_suspended_sales_workspace_idx');
            $table->index(['company_id', 'branch_id', 'claimed_at'], 'pos_suspended_sales_claim_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_suspended_sales');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_credit_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('customer_id');
            $table->string('branch_id', 100)->nullable();
            $table->string('branch_name')->nullable();
            $table->string('fingerprint', 64);
            $table->json('details');
            $table->decimal('credit_amount', 18, 2);
            $table->decimal('projected_outstanding', 18, 2);
            $table->decimal('credit_limit', 18, 2);
            $table->string('status', 16)->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'requested_by', 'fingerprint'], 'pos_credit_approval_fingerprint');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_credit_approvals');
    }
};

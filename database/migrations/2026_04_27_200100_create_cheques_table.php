<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cheque management
        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('cheque_number', 100);
            $table->string('type')->default('issue')->comment('issue,receive');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('payee_name')->nullable();
            $table->string('drawer_name')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('currency', 10)->default('NGN');
            $table->date('cheque_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending')
                ->comment('pending,cleared,bounced,cancelled,voided,deposited');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'cheque_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};

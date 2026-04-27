<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Loan / overdraft tracking
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('loan_number', 100)->nullable();
            $table->string('type')->default('loan')->comment('loan,overdraft,credit_line');
            $table->string('lender_name');
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->decimal('principal_amount', 18, 2);
            $table->decimal('interest_rate', 8, 4)->default(0);
            $table->string('interest_type')->default('fixed')->comment('fixed,reducing');
            $table->date('disbursement_date');
            $table->date('maturity_date')->nullable();
            $table->integer('tenure_months')->nullable();
            $table->string('repayment_frequency')->default('monthly')
                ->comment('weekly,monthly,quarterly,annually,bullet');
            $table->decimal('emi_amount', 18, 2)->nullable();
            $table->decimal('outstanding_balance', 18, 2)->default(0);
            $table->string('status')->default('active')->comment('active,closed,defaulted,restructured');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('company_id')->index();
            $table->date('payment_date');
            $table->decimal('principal_paid', 18, 2)->default(0);
            $table->decimal('interest_paid', 18, 2)->default(0);
            $table->decimal('total_paid', 18, 2);
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->index(['loan_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('loans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_branch_audit_findings', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 64)->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('source_table', 120)->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('status', 40)->index();
            $table->string('issue_type', 80)->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('reference', 191)->nullable();
            $table->string('account_name', 191)->nullable();
            $table->decimal('debit', 18, 2)->nullable();
            $table->decimal('credit', 18, 2)->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->string('current_branch_id', 120)->nullable();
            $table->string('current_branch_name', 191)->nullable();
            $table->string('proposed_branch_id', 120)->nullable();
            $table->string('proposed_branch_name', 191)->nullable();
            $table->string('related_summary', 255)->nullable();
            $table->string('action_taken', 80)->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index(['source_table', 'source_id'], 'abaf_source_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_branch_audit_findings');
    }
};

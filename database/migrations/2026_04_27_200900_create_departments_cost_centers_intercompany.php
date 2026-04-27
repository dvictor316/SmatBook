<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Departments
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->comment('for hierarchy');
            $table->unsignedBigInteger('head_employee_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });

        // Cost Centers
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('type')->default('operational')
                ->comment('operational,project,department,branch,profit_center,investment_center');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });

        // Add cost_center_id and department_id to key transaction tables
        $tables = ['expenses', 'transactions', 'purchase_requisitions'];
        foreach ($tables as $t) {
            if (Schema::hasTable($t)) {
                Schema::table($t, function (Blueprint $table) use ($t) {
                    if (!Schema::hasColumn($t, 'department_id')) {
                        $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
                    }
                    if (!Schema::hasColumn($t, 'cost_center_id')) {
                        $table->unsignedBigInteger('cost_center_id')->nullable()->after('department_id');
                    }
                });
            }
        }

        // Intercompany Transactions
        Schema::create('intercompany_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index()->comment('source company');
            $table->unsignedBigInteger('counterparty_company_id')->comment('target company');
            $table->string('reference_number', 100)->nullable();
            $table->string('transaction_type')->default('sale')
                ->comment('sale,purchase,loan,dividend,management_fee,transfer');
            $table->date('transaction_date');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 10)->default('NGN');
            $table->string('status')->default('draft')
                ->comment('draft,posted,matched,cancelled');
            $table->unsignedBigInteger('source_account_id')->nullable();
            $table->unsignedBigInteger('target_account_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'counterparty_company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intercompany_transactions');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('departments');
    }
};

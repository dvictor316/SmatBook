<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deployment_managers') && !Schema::hasColumn('deployment_managers', 'approved_at')) {
            Schema::table('deployment_managers', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('status');
            });
        }

        if (!Schema::hasTable('payroll_runs')) {
            Schema::create('payroll_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->string('period');
                $table->date('pay_date')->nullable();
                $table->string('payment_method')->default('bank_transfer');
                $table->text('notes')->nullable();
                $table->enum('status', ['draft', 'processing', 'completed', 'failed'])->default('draft');
                $table->integer('staff_count')->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deployment_managers') && Schema::hasColumn('deployment_managers', 'approved_at')) {
            Schema::table('deployment_managers', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }

        // Intentionally do not drop payroll_runs on rollback to avoid accidental data loss.
    }
};

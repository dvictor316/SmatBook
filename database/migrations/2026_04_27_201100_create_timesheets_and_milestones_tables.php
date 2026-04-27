<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Timesheets
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('week_start_date');
            $table->string('status')->default('draft')
                ->comment('draft,submitted,approved,rejected,invoiced');
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->decimal('billable_hours', 8, 2)->default(0);
            $table->decimal('hourly_rate', 18, 4)->nullable();
            $table->decimal('billable_amount', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'project_id', 'status']);
        });

        Schema::create('timesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('timesheet_id');
            $table->date('entry_date');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->string('activity_description');
            $table->decimal('hours', 6, 2);
            $table->boolean('is_billable')->default(true);
            $table->decimal('hourly_rate', 18, 4)->nullable();
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->foreign('timesheet_id')->references('id')->on('timesheets')->onDelete('cascade');
        });

        // Project Milestones for milestone billing
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->decimal('billing_amount', 18, 2)->default(0);
            $table->string('billing_type')->default('fixed')
                ->comment('fixed,percentage_of_contract,on_completion');
            $table->decimal('percentage', 6, 2)->nullable();
            $table->string('status')->default('pending')
                ->comment('pending,in_progress,completed,billed,cancelled');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('timesheet_entries');
        Schema::dropIfExists('timesheets');
    }
};

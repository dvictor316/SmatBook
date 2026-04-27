<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leave Types
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->integer('days_allowed_per_year')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('carry_forward')->default(false);
            $table->integer('max_carry_forward_days')->default(0);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Leave Requests
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_requested', 6, 1);
            $table->string('status')->default('pending')
                ->comment('pending,approved,rejected,cancelled,withdrawn');
            $table->text('reason')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'employee_id', 'status']);
        });

        // Leave Balances
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->integer('year');
            $table->decimal('entitled_days', 6, 1)->default(0);
            $table->decimal('used_days', 6, 1)->default(0);
            $table->decimal('pending_days', 6, 1)->default(0);
            $table->decimal('remaining_days', 6, 1)->default(0);
            $table->decimal('carried_forward_days', 6, 1)->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'leave_type_id', 'year']);
        });

        // Attendance Records
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id');
            $table->date('attendance_date');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->decimal('hours_worked', 6, 2)->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->string('status')->default('present')
                ->comment('present,absent,late,half_day,on_leave,holiday,remote');
            $table->string('check_in_method')->nullable()
                ->comment('manual,biometric,mobile,web');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'attendance_date']);
            $table->index(['company_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
    }
};

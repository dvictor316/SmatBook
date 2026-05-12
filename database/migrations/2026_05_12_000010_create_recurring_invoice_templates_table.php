<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoice_templates', function (Blueprint $table) {
            $table->id();

            // Tenant & branch isolation
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Customer info
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('customer_name')->nullable();

            // Template metadata
            $table->string('template_name');
            $table->text('notes')->nullable();
            $table->text('internal_memo')->nullable();
            $table->unsignedBigInteger('salesperson_id')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->string('currency', 10)->default('NGN');
            $table->string('terms')->nullable();
            $table->unsignedInteger('due_days')->default(30);

            // Recurrence rules
            $table->string('frequency', 30)->default('monthly');
            // Values: daily|weekly|biweekly|monthly|quarterly|semi_annual|annual|custom
            $table->unsignedInteger('interval_value')->default(1);
            $table->string('interval_unit', 20)->default('months');
            // Values: days|weeks|months|years (used when frequency = custom)

            // Date rules
            $table->string('date_rule', 30)->default('specific_day');
            // Values: specific_day|first_of_month|last_of_month|business_day
            $table->unsignedTinyInteger('specific_day')->nullable();
            // Day of month (1–31) when date_rule = specific_day
            $table->boolean('skip_weekends')->default(false);

            // Automation
            $table->string('automation_mode', 30)->default('draft');
            // Values: draft|auto_send|reminder_only|manual

            // Schedule
            $table->date('starts_on');
            $table->date('next_run_on')->nullable()->index();
            $table->timestamp('last_run_on')->nullable();
            $table->string('end_type', 20)->default('never');
            // Values: never|date|count
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('max_occurrences')->nullable();
            $table->unsignedInteger('occurrences_count')->default(0);

            // Status
            $table->string('status', 30)->default('active')->index();
            // Values: active|paused|completed|cancelled|archived

            // Items stored as JSON snapshot (mirrors sale_items structure)
            $table->json('items')->nullable();

            // Cached totals
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Notification config
            $table->boolean('send_email')->default(true);
            $table->string('email_subject')->nullable();
            $table->json('reminder_before_days')->nullable();
            $table->json('reminder_after_days')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoice_templates');
    }
};

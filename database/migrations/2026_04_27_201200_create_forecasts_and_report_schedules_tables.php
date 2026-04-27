<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Forecasting / Scenario Planning
        Schema::create('forecasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('name');
            $table->string('type')->default('revenue')
                ->comment('revenue,expense,cash_flow,sales,budget');
            $table->string('scenario')->default('base')
                ->comment('base,optimistic,pessimistic,custom');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('frequency')->default('monthly')
                ->comment('weekly,monthly,quarterly,annually');
            $table->string('status')->default('draft')
                ->comment('draft,active,archived');
            $table->decimal('total_forecast_amount', 18, 2)->default(0);
            $table->decimal('actual_amount', 18, 2)->default(0);
            $table->text('assumptions')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('forecast_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('forecast_id');
            $table->date('period_date');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('category')->nullable();
            $table->decimal('forecast_amount', 18, 2)->default(0);
            $table->decimal('actual_amount', 18, 2)->default(0);
            $table->decimal('variance', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('forecast_id')->references('id')->on('forecasts')->onDelete('cascade');
        });

        // Report Schedules
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->string('report_type');
            $table->string('frequency')->default('monthly')
                ->comment('daily,weekly,monthly,quarterly,annually');
            $table->string('format')->default('pdf')
                ->comment('pdf,excel,csv');
            $table->json('recipients')->nullable();
            $table->json('parameters')->nullable()->comment('date ranges, filters, etc');
            $table->string('cron_expression')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('forecast_items');
        Schema::dropIfExists('forecasts');
    }
};

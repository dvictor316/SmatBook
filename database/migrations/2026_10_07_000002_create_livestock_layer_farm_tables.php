<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livestock_farms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable();
            $table->string('name');
            $table->string('code', 40);
            $table->string('farm_type', 30)->default('layers')->index();
            $table->unsignedInteger('bird_capacity')->default(0);
            $table->unsignedInteger('eggs_per_crate')->default(30);
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('livestock_flocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('farm_id')->index();
            $table->string('batch_code', 60);
            $table->string('breed')->nullable();
            $table->date('placement_date');
            $table->unsignedInteger('age_at_placement_weeks')->default(14);
            $table->unsignedInteger('opening_birds');
            $table->unsignedInteger('current_birds');
            $table->decimal('cost_per_bird', 14, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->date('closed_on')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['company_id', 'batch_code']);
        });

        Schema::create('livestock_investments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('farm_id')->index();
            $table->unsignedBigInteger('flock_id')->nullable()->index();
            $table->string('cost_class', 30)->index();
            $table->string('category', 60)->index();
            $table->string('description');
            $table->date('cost_date')->index();
            $table->decimal('cost', 16, 2);
            $table->decimal('salvage_value', 16, 2)->default(0);
            $table->unsignedInteger('useful_life_months')->default(12);
            $table->string('allocation_method', 30)->default('straight_line');
            $table->decimal('accumulated_allocation', 16, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('livestock_opex_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('farm_id')->index();
            $table->unsignedBigInteger('flock_id')->nullable()->index();
            $table->date('expense_date')->index();
            $table->string('category', 50)->index();
            $table->string('description')->nullable();
            $table->decimal('quantity', 14, 3)->default(1);
            $table->string('unit', 30)->nullable();
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('amount', 16, 2);
            $table->string('frequency', 20)->default('daily');
            $table->string('vendor')->nullable();
            $table->string('reference', 100)->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('livestock_revenue_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('farm_id')->index();
            $table->unsignedBigInteger('flock_id')->nullable()->index();
            $table->date('revenue_date')->index();
            $table->string('source', 50)->index();
            $table->string('description')->nullable();
            $table->decimal('quantity', 14, 3)->default(1);
            $table->string('unit', 30)->nullable();
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('amount', 16, 2);
            $table->string('customer')->nullable();
            $table->string('reference', 100)->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('livestock_daily_productions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('farm_id')->index();
            $table->unsignedBigInteger('flock_id')->index();
            $table->date('production_date')->index();
            $table->unsignedInteger('opening_birds');
            $table->unsignedInteger('mortality')->default(0);
            $table->unsignedInteger('culled')->default(0);
            $table->unsignedInteger('closing_birds');
            $table->unsignedInteger('egg_crates')->default(0);
            $table->unsignedInteger('loose_eggs')->default(0);
            $table->unsignedInteger('damaged_eggs')->default(0);
            $table->unsignedInteger('total_good_eggs')->default(0);
            $table->decimal('feed_kg', 14, 3)->default(0);
            $table->decimal('water_litres', 14, 3)->default(0);
            $table->decimal('hen_day_percent', 8, 2)->default(0);
            $table->string('medication')->nullable();
            $table->string('vaccination')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['farm_id', 'flock_id', 'production_date'], 'livestock_daily_production_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestock_daily_productions');
        Schema::dropIfExists('livestock_revenue_entries');
        Schema::dropIfExists('livestock_opex_entries');
        Schema::dropIfExists('livestock_investments');
        Schema::dropIfExists('livestock_flocks');
        Schema::dropIfExists('livestock_farms');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asset Maintenance Logs
        Schema::create('asset_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('fixed_asset_id');
            $table->string('maintenance_type')->default('preventive')
                ->comment('preventive,corrective,inspection,upgrade');
            $table->date('maintenance_date');
            $table->date('next_maintenance_date')->nullable();
            $table->string('performed_by')->nullable();
            $table->string('vendor_name')->nullable();
            $table->decimal('cost', 18, 2)->default(0);
            $table->string('status')->default('completed')
                ->comment('scheduled,in_progress,completed,cancelled');
            $table->text('description');
            $table->text('findings')->nullable();
            $table->text('parts_replaced')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'fixed_asset_id']);
        });

        // Multiple depreciation methods — add method column to fixed_assets
        Schema::table('fixed_assets', function (Blueprint $table) {
            if (!Schema::hasColumn('fixed_assets', 'depreciation_method')) {
                $table->string('depreciation_method', 30)->default('straight_line')
                    ->comment('straight_line,declining_balance,double_declining,sum_of_years,units_of_production')
                    ->nullable()->after('depreciation_rate');
            }
            if (!Schema::hasColumn('fixed_assets', 'revalued_amount')) {
                $table->decimal('revalued_amount', 18, 2)->nullable()->after('depreciation_method');
            }
            if (!Schema::hasColumn('fixed_assets', 'revaluation_date')) {
                $table->date('revaluation_date')->nullable()->after('revalued_amount');
            }
            if (!Schema::hasColumn('fixed_assets', 'maintenance_schedule')) {
                $table->string('maintenance_schedule', 30)->nullable()
                    ->comment('monthly,quarterly,semi_annually,annually')
                    ->after('revaluation_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            foreach (['depreciation_method', 'revalued_amount', 'revaluation_date', 'maintenance_schedule'] as $col) {
                if (Schema::hasColumn('fixed_assets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::dropIfExists('asset_maintenance_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            if (!Schema::hasColumn('fixed_assets', 'depreciation_frequency')) {
                $table->string('depreciation_frequency', 20)->default('monthly')->after('depreciation_method')->index();
            }

            if (!Schema::hasColumn('fixed_assets', 'next_depreciation_on')) {
                $table->date('next_depreciation_on')->nullable()->after('last_depreciated_on')->index();
            }
        });

        Schema::table('fixed_asset_depreciations', function (Blueprint $table) {
            if (!Schema::hasColumn('fixed_asset_depreciations', 'period_start_on')) {
                $table->date('period_start_on')->nullable()->after('run_date')->index();
            }

            if (!Schema::hasColumn('fixed_asset_depreciations', 'period_end_on')) {
                $table->date('period_end_on')->nullable()->after('period_start_on')->index();
            }

            if (!Schema::hasColumn('fixed_asset_depreciations', 'depreciation_frequency')) {
                $table->string('depreciation_frequency', 20)->nullable()->after('period_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fixed_asset_depreciations', function (Blueprint $table) {
            if (Schema::hasColumn('fixed_asset_depreciations', 'depreciation_frequency')) {
                $table->dropColumn('depreciation_frequency');
            }
            if (Schema::hasColumn('fixed_asset_depreciations', 'period_end_on')) {
                $table->dropColumn('period_end_on');
            }
            if (Schema::hasColumn('fixed_asset_depreciations', 'period_start_on')) {
                $table->dropColumn('period_start_on');
            }
        });

        Schema::table('fixed_assets', function (Blueprint $table) {
            if (Schema::hasColumn('fixed_assets', 'next_depreciation_on')) {
                $table->dropColumn('next_depreciation_on');
            }
            if (Schema::hasColumn('fixed_assets', 'depreciation_frequency')) {
                $table->dropColumn('depreciation_frequency');
            }
        });
    }
};

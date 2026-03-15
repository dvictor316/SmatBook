<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployment_managers', function (Blueprint $table) {
            if (!Schema::hasColumn('deployment_managers', 'payout_bank_name')) {
                $table->string('payout_bank_name')->nullable()->after('commission_rate');
            }
            if (!Schema::hasColumn('deployment_managers', 'payout_bank_code')) {
                $table->string('payout_bank_code', 50)->nullable()->after('payout_bank_name');
            }
            if (!Schema::hasColumn('deployment_managers', 'payout_account_name')) {
                $table->string('payout_account_name')->nullable()->after('payout_bank_code');
            }
            if (!Schema::hasColumn('deployment_managers', 'payout_account_number')) {
                $table->string('payout_account_number', 100)->nullable()->after('payout_account_name');
            }
            if (!Schema::hasColumn('deployment_managers', 'payout_provider')) {
                $table->string('payout_provider', 50)->nullable()->after('payout_account_number');
            }
            if (!Schema::hasColumn('deployment_managers', 'payout_recipient_code')) {
                $table->string('payout_recipient_code')->nullable()->after('payout_provider');
            }
            if (!Schema::hasColumn('deployment_managers', 'payout_status')) {
                $table->string('payout_status', 50)->default('not_configured')->after('payout_recipient_code');
            }
            if (!Schema::hasColumn('deployment_managers', 'auto_payout_enabled')) {
                $table->boolean('auto_payout_enabled')->default(false)->after('payout_status');
            }
            if (!Schema::hasColumn('deployment_managers', 'minimum_payout_amount')) {
                $table->decimal('minimum_payout_amount', 15, 2)->default(5000)->after('auto_payout_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deployment_managers', function (Blueprint $table) {
            $columns = [
                'payout_bank_name',
                'payout_bank_code',
                'payout_account_name',
                'payout_account_number',
                'payout_provider',
                'payout_recipient_code',
                'payout_status',
                'auto_payout_enabled',
                'minimum_payout_amount',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('deployment_managers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

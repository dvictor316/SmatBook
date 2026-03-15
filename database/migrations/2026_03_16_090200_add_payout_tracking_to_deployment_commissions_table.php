<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployment_commissions', function (Blueprint $table) {
            if (!Schema::hasColumn('deployment_commissions', 'payout_id')) {
                $table->foreignId('payout_id')->nullable()->after('status')->constrained('deployment_manager_payouts')->nullOnDelete();
            }
            if (!Schema::hasColumn('deployment_commissions', 'payout_reference')) {
                $table->string('payout_reference')->nullable()->after('payout_id');
            }
            if (!Schema::hasColumn('deployment_commissions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('processed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deployment_commissions', function (Blueprint $table) {
            foreach (['payout_id', 'payout_reference', 'paid_at'] as $column) {
                if (Schema::hasColumn('deployment_commissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

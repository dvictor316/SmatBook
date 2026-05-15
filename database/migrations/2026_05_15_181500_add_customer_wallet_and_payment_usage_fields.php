<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'wallet_balance')) {
                $table->decimal('wallet_balance', 15, 2)->default(0)->after('credit_limit');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'source')) {
                $table->string('source', 80)->nullable()->after('method');
            }
            if (!Schema::hasColumn('payments', 'wallet_amount')) {
                $table->decimal('wallet_amount', 15, 2)->default(0)->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'wallet_amount')) {
                $table->dropColumn('wallet_amount');
            }
            if (Schema::hasColumn('payments', 'source')) {
                $table->dropColumn('source');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'wallet_balance')) {
                $table->dropColumn('wallet_balance');
            }
        });
    }
};

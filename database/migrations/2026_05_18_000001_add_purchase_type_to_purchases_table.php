<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'purchase_type')) {
                $table->string('purchase_type', 30)->nullable()->default('inventory')->after('status');
            }
            if (!Schema::hasColumn('purchases', 'asset_account_id')) {
                $table->unsignedBigInteger('asset_account_id')->nullable()->after('purchase_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'asset_account_id')) {
                $table->dropColumn('asset_account_id');
            }
            if (Schema::hasColumn('purchases', 'purchase_type')) {
                $table->dropColumn('purchase_type');
            }
        });
    }
};

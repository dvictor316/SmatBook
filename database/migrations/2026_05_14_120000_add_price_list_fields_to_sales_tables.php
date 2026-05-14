<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'price_list_id')) {
                    $table->unsignedBigInteger('price_list_id')->nullable()->after('customer_id')->index();
                }
            });
        }

        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_items', 'price_list_id')) {
                    $table->unsignedBigInteger('price_list_id')->nullable()->after('product_id')->index();
                }
                if (!Schema::hasColumn('sale_items', 'price_level')) {
                    $table->string('price_level', 32)->nullable()->after('price_list_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                if (Schema::hasColumn('sale_items', 'price_level')) {
                    $table->dropColumn('price_level');
                }
                if (Schema::hasColumn('sale_items', 'price_list_id')) {
                    $table->dropColumn('price_list_id');
                }
            });
        }

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (Schema::hasColumn('sales', 'price_list_id')) {
                    $table->dropColumn('price_list_id');
                }
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock', 18, 6)->default(0)->change();
            $table->decimal('stock_quantity', 18, 6)->default(0)->change();
            $table->decimal('units_per_carton', 18, 6)->default(1)->change();
            $table->decimal('units_per_roll', 18, 6)->default(1)->change();
        });

        if (Schema::hasTable('product_branch_stocks')) {
            Schema::table('product_branch_stocks', function (Blueprint $table) {
                $table->decimal('quantity', 18, 6)->default(0)->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock')->default(0)->change();
            $table->integer('stock_quantity')->default(0)->change();
            $table->integer('units_per_carton')->default(1)->change();
            $table->integer('units_per_roll')->default(1)->change();
        });

        if (Schema::hasTable('product_branch_stocks')) {
            Schema::table('product_branch_stocks', function (Blueprint $table) {
                $table->decimal('quantity', 15, 2)->default(0)->change();
            });
        }
    }
};

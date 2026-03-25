<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'retail_price')) {
                $table->decimal('retail_price', 15, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('products', 'wholesale_price')) {
                $table->decimal('wholesale_price', 15, 2)->nullable()->after('retail_price');
            }
            if (!Schema::hasColumn('products', 'special_price')) {
                $table->decimal('special_price', 15, 2)->nullable()->after('wholesale_price');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $drops = [];

            foreach (['retail_price', 'wholesale_price', 'special_price'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $drops[] = $column;
                }
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'units_per_carton')) {
                $table->integer('units_per_carton')->default(1)->after('price');
            }
            if (! Schema::hasColumn('products', 'units_per_roll')) {
                $table->integer('units_per_roll')->default(1)->after('units_per_carton');
            }
            if (! Schema::hasColumn('products', 'base_unit_name')) {
                $table->string('base_unit_name')->default('pcs')->after('units_per_roll');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            foreach (['units_per_carton', 'units_per_roll', 'base_unit_name'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

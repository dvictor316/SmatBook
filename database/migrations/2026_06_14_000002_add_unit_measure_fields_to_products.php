<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'unit_id')) {
                $table->unsignedBigInteger('unit_id')->nullable()->after('category_id')->index();
            }
            if (!Schema::hasColumn('products', 'base_unit_id')) {
                $table->unsignedBigInteger('base_unit_id')->nullable()->after('unit_id')->index();
            }
            if (!Schema::hasColumn('products', 'purchase_unit_id')) {
                $table->unsignedBigInteger('purchase_unit_id')->nullable()->after('base_unit_id')->index();
            }
            if (!Schema::hasColumn('products', 'conversion_rate')) {
                $table->decimal('conversion_rate', 18, 6)->nullable()->after('purchase_unit_id');
            }
        });

        if (Schema::hasTable('units')) {
            $pieceUnitId = DB::table('units')->whereNull('company_id')->where('symbol', 'pcs')->value('id')
                ?: DB::table('units')->where('status', 'active')->value('id');

            if ($pieceUnitId) {
                DB::table('products')
                    ->whereNull('unit_id')
                    ->update(['unit_id' => $pieceUnitId]);

                DB::table('products')
                    ->whereNull('base_unit_id')
                    ->update(['base_unit_id' => $pieceUnitId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['conversion_rate', 'purchase_unit_id', 'base_unit_id', 'unit_id'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

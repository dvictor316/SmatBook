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
    Schema::table('sale_items', function (Blueprint $table) {
        if (!Schema::hasColumn('sale_items', 'tax')) {
            $table->decimal('tax', 8, 2)->default(0)->after('unit_price');
        }
        if (!Schema::hasColumn('sale_items', 'discount')) {
            $table->decimal('discount', 8, 2)->default(0)->after('tax');
        }
        if (!Schema::hasColumn('sale_items', 'subtotal')) {
            $table->decimal('subtotal', 15, 2)->default(0)->after('qty');
        }
        // This is the one causing the "Duplicate column" error, so we check first
        if (!Schema::hasColumn('sale_items', 'total_price')) {
            $table->decimal('total_price', 15, 2)->default(0)->after('subtotal');
        }
    });
}

public function down(): void
{
    Schema::table('sale_items', function (Blueprint $table) {
        $table->dropColumn(['tax', 'discount', 'subtotal', 'total_price']);
    });
}
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_items', 'description')) {
                $table->string('description')->nullable()->after('product_id');
            }

            if (!Schema::hasColumn('purchase_items', 'received_qty')) {
                $table->decimal('received_qty', 18, 4)->default(0)->after('qty');
            }

            if (!Schema::hasColumn('purchase_items', 'line_total')) {
                $table->decimal('line_total', 18, 2)->default(0)->after('unit_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_items', 'line_total')) {
                $table->dropColumn('line_total');
            }

            if (Schema::hasColumn('purchase_items', 'received_qty')) {
                $table->dropColumn('received_qty');
            }

            if (Schema::hasColumn('purchase_items', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};

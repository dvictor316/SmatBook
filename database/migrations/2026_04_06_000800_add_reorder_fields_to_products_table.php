<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'reorder_level')) {
                $table->unsignedInteger('reorder_level')->nullable();
            }
            if (!Schema::hasColumn('products', 'reorder_quantity')) {
                $table->unsignedInteger('reorder_quantity')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'reorder_quantity')) {
                $table->dropColumn('reorder_quantity');
            }
            if (Schema::hasColumn('products', 'reorder_level')) {
                $table->dropColumn('reorder_level');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_branch_stocks') && !Schema::hasColumn('product_branch_stocks', 'is_available')) {
            Schema::table('product_branch_stocks', function (Blueprint $table) {
                $table->boolean('is_available')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_branch_stocks') && Schema::hasColumn('product_branch_stocks', 'is_available')) {
            Schema::table('product_branch_stocks', function (Blueprint $table) {
                $table->dropColumn('is_available');
            });
        }
    }
};

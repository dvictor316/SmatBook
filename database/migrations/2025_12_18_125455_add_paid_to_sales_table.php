<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Only add 'paid' if it doesn't exist
            if (!Schema::hasColumn('sales', 'paid')) {
                $table->decimal('paid', 15, 2)->default(0)->after('total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'paid')) {
                $table->dropColumn('paid');
            }
        });
    }
};
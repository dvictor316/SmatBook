<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            if (!Schema::hasColumn('estimates', 'price_list_id')) {
                $table->unsignedBigInteger('price_list_id')->nullable()->after('customer_id')->index();
            }
            if (!Schema::hasColumn('estimates', 'items')) {
                $table->json('items')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            if (Schema::hasColumn('estimates', 'items')) {
                $table->dropColumn('items');
            }
            if (Schema::hasColumn('estimates', 'price_list_id')) {
                $table->dropColumn('price_list_id');
            }
        });
    }
};

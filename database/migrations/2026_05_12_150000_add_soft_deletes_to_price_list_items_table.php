<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_list_items', function (Blueprint $table) {
            if (!Schema::hasColumn('price_list_items', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('price_list_items', function (Blueprint $table) {
            if (Schema::hasColumn('price_list_items', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

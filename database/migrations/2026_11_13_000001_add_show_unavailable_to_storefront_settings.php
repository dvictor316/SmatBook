<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->boolean('show_unavailable_products')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('storefront_settings', function (Blueprint $table) {
            $table->dropColumn('show_unavailable_products');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('storefront_settings')
            ->where('slug', 'blinqit-royal-success')
            ->update(['show_unavailable_products' => true]);
    }

    public function down(): void
    {
        // Preserve any subsequent choice made by the store owner.
    }
};

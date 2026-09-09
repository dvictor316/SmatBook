<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('credit_notes')
            || !Schema::hasTable('sales')
            || !Schema::hasColumn('credit_notes', 'sale_id')
        ) {
            return;
        }

        if (Schema::hasColumn('credit_notes', 'branch_id') && Schema::hasColumn('sales', 'branch_id')) {
            DB::statement("
                UPDATE credit_notes cn
                JOIN sales s ON s.id = cn.sale_id
                SET cn.branch_id = s.branch_id
                WHERE cn.branch_id IS NULL
                  AND s.branch_id IS NOT NULL
            ");
        }

        if (Schema::hasColumn('credit_notes', 'branch_name') && Schema::hasColumn('sales', 'branch_name')) {
            DB::statement("
                UPDATE credit_notes cn
                JOIN sales s ON s.id = cn.sale_id
                SET cn.branch_name = s.branch_name
                WHERE cn.branch_name IS NULL
                  AND s.branch_name IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        // Data backfill only; intentionally not reversible.
    }
};

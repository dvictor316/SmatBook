<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfer_audits', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'stock_transfer_audits_company_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_audits', function (Blueprint $table) {
            $table->dropIndex('stock_transfer_audits_company_created_at_index');
        });
    }
};

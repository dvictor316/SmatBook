<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table) {
                if (!Schema::hasColumn('quotations', 'converted_to_type')) {
                    $table->string('converted_to_type', 40)->nullable()->after('status')->index();
                }
                if (!Schema::hasColumn('quotations', 'converted_sale_id')) {
                    $table->unsignedBigInteger('converted_sale_id')->nullable()->after('converted_to_type')->index();
                }
                if (!Schema::hasColumn('quotations', 'converted_receipt_no')) {
                    $table->string('converted_receipt_no', 80)->nullable()->after('converted_sale_id');
                }
                if (!Schema::hasColumn('quotations', 'converted_at')) {
                    $table->timestamp('converted_at')->nullable()->after('converted_receipt_no');
                }
            });
        }

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'source_type')) {
                    $table->string('source_type', 40)->nullable()->after('terminal_id')->index();
                }
                if (!Schema::hasColumn('sales', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type')->index();
                }
                if (!Schema::hasColumn('sales', 'source_reference')) {
                    $table->string('source_reference', 120)->nullable()->after('source_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                foreach (['source_reference', 'source_id', 'source_type'] as $column) {
                    if (Schema::hasColumn('sales', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table) {
                foreach (['converted_at', 'converted_receipt_no', 'converted_sale_id', 'converted_to_type'] as $column) {
                    if (Schema::hasColumn('quotations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

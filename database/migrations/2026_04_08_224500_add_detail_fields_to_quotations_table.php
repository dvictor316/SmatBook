<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('quotations', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('branch_name');
            }
            if (!Schema::hasColumn('quotations', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('issue_date');
            }
            if (!Schema::hasColumn('quotations', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('expiry_date');
            }
            if (!Schema::hasColumn('quotations', 'tax')) {
                $table->decimal('tax', 15, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('quotations', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0)->after('tax');
            }
            if (!Schema::hasColumn('quotations', 'description')) {
                $table->text('description')->nullable()->after('status');
            }
            if (!Schema::hasColumn('quotations', 'items_json')) {
                $table->longText('items_json')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            foreach (['items_json', 'description', 'discount', 'tax', 'subtotal', 'expiry_date', 'issue_date', 'customer_name'] as $column) {
                if (Schema::hasColumn('quotations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

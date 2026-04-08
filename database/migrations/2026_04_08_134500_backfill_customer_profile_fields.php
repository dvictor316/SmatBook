<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        $hasCustomerName = Schema::hasColumn('customers', 'customer_name');
        $hasAddress = Schema::hasColumn('customers', 'address');

        if ($hasCustomerName && Schema::hasColumn('customers', 'billing_name')) {
            DB::table('customers')
                ->where(function ($query) {
                    $query->whereNull('billing_name')
                        ->orWhere('billing_name', '');
                })
                ->update([
                    'billing_name' => DB::raw('customer_name'),
                ]);

            DB::table('customers')
                ->where(function ($query) {
                    $query->whereNull('shipping_name')
                        ->orWhere('shipping_name', '');
                })
                ->update([
                    'shipping_name' => DB::raw('customer_name'),
                ]);
        }

        if ($hasAddress && Schema::hasColumn('customers', 'billing_address_line1')) {
            DB::table('customers')
                ->where(function ($query) {
                    $query->whereNull('billing_address_line1')
                        ->orWhere('billing_address_line1', '');
                })
                ->whereNotNull('address')
                ->where('address', '<>', '')
                ->update([
                    'billing_address_line1' => DB::raw('address'),
                ]);

            DB::table('customers')
                ->where(function ($query) {
                    $query->whereNull('shipping_address_line1')
                        ->orWhere('shipping_address_line1', '');
                })
                ->whereNotNull('address')
                ->where('address', '<>', '')
                ->update([
                    'shipping_address_line1' => DB::raw('address'),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally left empty. This migration backfills existing data only.
    }
};

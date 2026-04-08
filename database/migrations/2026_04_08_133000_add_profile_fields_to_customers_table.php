<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'currency')) {
                $table->string('currency')->nullable();
            }
            if (!Schema::hasColumn('customers', 'website')) {
                $table->string('website')->nullable();
            }
            if (!Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (!Schema::hasColumn('customers', 'billing_name')) {
                $table->string('billing_name')->nullable();
            }
            if (!Schema::hasColumn('customers', 'billing_address_line1')) {
                $table->string('billing_address_line1')->nullable();
            }
            if (!Schema::hasColumn('customers', 'billing_address_line2')) {
                $table->string('billing_address_line2')->nullable();
            }
            if (!Schema::hasColumn('customers', 'billing_country')) {
                $table->string('billing_country')->nullable();
            }
            if (!Schema::hasColumn('customers', 'billing_city')) {
                $table->string('billing_city')->nullable();
            }
            if (!Schema::hasColumn('customers', 'billing_state')) {
                $table->string('billing_state')->nullable();
            }
            if (!Schema::hasColumn('customers', 'billing_pincode')) {
                $table->string('billing_pincode')->nullable();
            }

            if (!Schema::hasColumn('customers', 'shipping_name')) {
                $table->string('shipping_name')->nullable();
            }
            if (!Schema::hasColumn('customers', 'shipping_address_line1')) {
                $table->string('shipping_address_line1')->nullable();
            }
            if (!Schema::hasColumn('customers', 'shipping_address_line2')) {
                $table->string('shipping_address_line2')->nullable();
            }
            if (!Schema::hasColumn('customers', 'shipping_country')) {
                $table->string('shipping_country')->nullable();
            }
            if (!Schema::hasColumn('customers', 'shipping_city')) {
                $table->string('shipping_city')->nullable();
            }
            if (!Schema::hasColumn('customers', 'shipping_state')) {
                $table->string('shipping_state')->nullable();
            }
            if (!Schema::hasColumn('customers', 'shipping_pincode')) {
                $table->string('shipping_pincode')->nullable();
            }
        });

    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $columns = [
                'shipping_pincode',
                'shipping_state',
                'shipping_city',
                'shipping_country',
                'shipping_address_line2',
                'shipping_address_line1',
                'shipping_name',
                'billing_pincode',
                'billing_state',
                'billing_city',
                'billing_country',
                'billing_address_line2',
                'billing_address_line1',
                'billing_name',
                'notes',
                'website',
                'currency',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

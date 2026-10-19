<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            foreach (['alternate_phone' => 50, 'city' => 100, 'state' => 100, 'country' => 100,
                'website' => 255, 'tax_number' => 100, 'bank_name' => 191,
                'account_name' => 191, 'account_number' => 50] as $column => $length) {
                if (!Schema::hasColumn('suppliers', $column)) {
                    $table->string($column, $length)->nullable();
                }
            }
            foreach (['supply_description', 'notes'] as $column) {
                if (!Schema::hasColumn('suppliers', $column)) {
                    $table->text($column)->nullable();
                }
            }
            if (!Schema::hasColumn('suppliers', 'payment_terms_days')) {
                $table->unsignedSmallInteger('payment_terms_days')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Retain supplier information on rollback rather than discarding business records.
    }
};

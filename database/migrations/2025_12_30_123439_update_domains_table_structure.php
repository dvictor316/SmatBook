<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    // 1. Handle Renaming First
    Schema::table('domains', function (Blueprint $table) {
        if (Schema::hasColumn('domains', 'domain') && !Schema::hasColumn('domains', 'domain_name')) {
            $table->renameColumn('domain', 'domain_name');
        }
    });

    // 2. Add Missing Columns Safely
    Schema::table('domains', function (Blueprint $table) {
        if (!Schema::hasColumn('domains', 'customer_name')) {
            $table->string('customer_name')->after('id');
        }

        if (!Schema::hasColumn('domains', 'email')) {
            $table->string('email')->after('customer_name');
        }

        if (!Schema::hasColumn('domains', 'package_name')) {
            $table->string('package_name'); 
        }

        if (!Schema::hasColumn('domains', 'employees')) {
            $table->integer('employees')->default(0);
        }

        if (!Schema::hasColumn('domains', 'package_type')) {
            $table->string('package_type')->nullable();
        }

        if (!Schema::hasColumn('domains', 'expiry_date')) {
            $table->date('expiry_date')->nullable();
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

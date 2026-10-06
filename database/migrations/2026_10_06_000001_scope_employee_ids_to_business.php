<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'business_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_employee_id_unique');
            $table->unique(['business_id', 'employee_id'], 'employees_business_employee_id_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_business_employee_id_unique');
            $table->unique('employee_id', 'employees_employee_id_unique');
        });
    }
};

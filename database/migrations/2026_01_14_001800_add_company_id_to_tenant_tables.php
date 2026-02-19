<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    $tables = ['sales', 'products', 'expenses', 'invoices', 'activities'];

    foreach ($tables as $tableName) {
        // Only run if the table does NOT have the column yet
        if (!Schema::hasColumn($tableName, 'company_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                $table->index('company_id');
            });
        }
    }
}

public function down()
{
    foreach (['sales', 'products', 'expenses', 'invoices', 'activities'] as $tableName) {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}
};

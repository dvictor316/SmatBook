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
        Schema::table('companies', function (Blueprint $table) {
            // These lines are likely already defined in an earlier 'create_companies_table' migration.
            // Commenting them out resolves the 'Duplicate column name' error during migrate:fresh.

            // $table->string('country')->default('Nigeria')->after('address');
            // $table->string('currency_code')->default('₦')->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Only attempt to drop them if they were actually added in the up() method
            // $table->dropColumn(['country', 'currency_code']);
        });
    }
};
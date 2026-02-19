<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add the company_id column as a foreign key after customer_id
            $table->foreignId('company_id')->constrained()->after('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop the foreign key constraint and the column if rolling back
            $table->dropConstrainedForeignId('company_id');
        });
    }
};

<?php

// database/migrations/YYYY_MM_DD_HHMMSS_add_status_balance_image_to_customers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Add the missing columns with default values so existing rows don't break
            $table->string('status')->default('active')->after('address');
            $table->decimal('balance', 10, 2)->default(0.00)->after('status');
            $table->string('image')->nullable()->after('balance');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Define how to revert the changes
            $table->dropColumn(['status', 'balance', 'image']);
        });
    }
};

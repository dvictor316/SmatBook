
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Check if columns exist before adding to avoid errors
            if (!Schema::hasColumn('plans', 'billing_cycle')) {
                $table->string('billing_cycle')->nullable()->after('price');
            }
            if (!Schema::hasColumn('plans', 'features')) {
                $table->text('features')->nullable()->after('billing_cycle');
            }
            if (!Schema::hasColumn('plans', 'is_active')) {
                $table->boolean('is_active')->default(1)->after('status');
            }
            
            // Ensure price is decimal for financial accuracy
            $table->decimal('price', 15, 2)->change();
            
            // Add expiry_date if missing (from your MySQL select output)
            if (!Schema::hasColumn('plans', 'expiry_date')) {
                $table->timestamp('expiry_date')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // We usually don't drop columns in down() for production safety, 
            // but you can add dropColumn logic here if needed.
        });
    }
};

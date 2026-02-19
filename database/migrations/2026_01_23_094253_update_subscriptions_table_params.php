<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Ensure domain_prefix is nullable to prevent errors during partial setup
            $table->string('domain_prefix')->nullable()->change();
            
            // Add status if missing, or ensure default is set
            if (!Schema::hasColumn('subscriptions', 'status')) {
                $table->string('status')->default('Awaiting Payment');
            }

            // Ensure amount/price columns exist
            if (!Schema::hasColumn('subscriptions', 'amount')) {
                $table->decimal('amount', 15, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('domain_prefix')->nullable(false)->change();
        });
    }
};

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
    Schema::table('sales', function (Blueprint $table) {
        $table->decimal('amount_paid', 15, 2)->default(0)->after('paid');
        $table->decimal('change_amount', 15, 2)->default(0)->after('amount_paid');
        $table->text('amount_in_words')->nullable()->after('currency');
        $table->json('payment_details')->nullable()->after('payment_status');
    });
}

public function down(): void
{
    Schema::table('sales', function (Blueprint $table) {
        $table->dropColumn(['amount_paid', 'change_amount', 'amount_in_words', 'payment_details']);
    });
}
};

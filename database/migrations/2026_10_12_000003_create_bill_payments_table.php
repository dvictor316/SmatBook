<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('reference', 100)->unique();
            $table->string('provider', 40)->default('flutterwave');
            $table->string('country', 2)->default('NG');
            $table->string('category_code', 80)->nullable();
            $table->string('biller_code', 80);
            $table->string('item_code', 80);
            $table->string('product_name')->nullable();
            $table->string('customer_identifier');
            $table->string('customer_name')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('provider_fee', 15, 2)->default(0);
            $table->decimal('commission_earned', 15, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->string('status', 40)->default('pending')->index();
            $table->string('provider_reference')->nullable()->index();
            $table->string('provider_transaction_id')->nullable();
            $table->text('recharge_token')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};

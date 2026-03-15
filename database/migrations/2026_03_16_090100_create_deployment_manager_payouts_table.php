<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployment_manager_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->string('payout_reference')->unique();
            $table->string('gateway', 50)->nullable();
            $table->string('status', 50)->default('pending');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('NGN');
            $table->string('bank_name')->nullable();
            $table->string('bank_code', 50)->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number', 100)->nullable();
            $table->string('recipient_reference')->nullable();
            $table->string('transfer_reference')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->boolean('is_automatic')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['manager_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_manager_payouts');
    }
};

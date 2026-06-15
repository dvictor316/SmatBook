<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'wallet_balance')) {
                $table->decimal('wallet_balance', 15, 2)->default(0)->after('status');
            }
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('bank_name');
            $table->string('bank_code', 50);
            $table->string('account_number', 100);
            $table->string('account_name');
            $table->string('recipient_code')->nullable();
            $table->string('reference')->unique();
            $table->enum('status', ['pending', 'success', 'failed', 'reversed'])->default('pending');
            $table->string('paystack_transfer_code')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('paystack_transfer_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'wallet_balance')) {
                $table->dropColumn('wallet_balance');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_name');
            $table->decimal('amount', 15, 2);
            $table->enum('payout_type', ['dividend', 'commission', 'salary', 'refund', 'other'])->default('dividend');
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable(); // user_id of superadmin who recorded it
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_payouts');
    }
};

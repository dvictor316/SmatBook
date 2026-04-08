<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_runs')) {
            return;
        }

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->string('period');
            $table->date('pay_date')->nullable();
            $table->string('payment_method')->default('bank_transfer');
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'processing', 'completed', 'failed'])->default('draft');
            $table->integer('staff_count')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};

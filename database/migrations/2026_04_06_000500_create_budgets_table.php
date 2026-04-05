<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('name');
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->string('period_type', 30)->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};

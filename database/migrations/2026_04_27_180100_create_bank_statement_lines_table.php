<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->unsignedBigInteger('bank_statement_import_id')->index();
            $table->unsignedBigInteger('bank_id')->index();
            $table->date('line_date')->nullable()->index();
            $table->string('description')->nullable();
            $table->string('reference')->nullable()->index();
            $table->decimal('debit', 20, 2)->nullable();
            $table->decimal('credit', 20, 2)->nullable();
            $table->decimal('amount', 20, 2)->default(0);
            $table->decimal('balance', 20, 2)->nullable();
            $table->string('status', 30)->default('unmatched')->index();
            $table->json('raw_row')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->unsignedBigInteger('bank_id')->index();
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();
            $table->string('source_file_name');
            $table->string('stored_file_path')->nullable();
            $table->string('currency', 10)->nullable();
            $table->date('statement_date_from')->nullable()->index();
            $table->date('statement_date_to')->nullable()->index();
            $table->unsignedInteger('line_count')->default(0);
            $table->decimal('opening_balance', 20, 2)->nullable();
            $table->decimal('closing_balance', 20, 2)->nullable();
            $table->string('status', 30)->default('imported')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_imports');
    }
};

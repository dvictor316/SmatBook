<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withholding_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_jurisdiction_id')->constrained('tax_jurisdictions')->cascadeOnDelete();
            $table->string('name');
            $table->string('counterparty_type')->default('vendor');
            $table->decimal('rate', 8, 4)->default(0);
            $table->decimal('threshold_amount', 18, 2)->default(0);
            $table->string('account_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withholding_rules');
    }
};


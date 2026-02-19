<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_jurisdiction_id')->constrained('tax_jurisdictions')->cascadeOnDelete();
            $table->string('code');
            $table->string('description');
            $table->decimal('rate', 8, 4)->default(0);
            $table->string('type')->default('vat');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tax_jurisdiction_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_codes');
    }
};


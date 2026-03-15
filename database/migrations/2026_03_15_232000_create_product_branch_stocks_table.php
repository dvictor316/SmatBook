<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_branch_stocks')) {
            return;
        }

        Schema::create('product_branch_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('branch_id', 64)->nullable()->index();
            $table->string('branch_name')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_branch_stocks');
    }
};

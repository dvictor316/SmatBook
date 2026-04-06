<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('from_branch_id')->nullable()->index();
            $table->string('from_branch_name')->nullable();
            $table->string('to_branch_id')->nullable()->index();
            $table->string('to_branch_name')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_audits');
    }
};

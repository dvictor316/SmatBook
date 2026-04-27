<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landed_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('grn_id')->nullable()->index();
            $table->string('cost_type', 100);
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->nullable();
            $table->enum('allocation_method', ['by_value', 'by_weight', 'by_quantity', 'equal'])->default('by_value');
            $table->enum('status', ['pending', 'allocated'])->default('pending');
            $table->timestamp('allocated_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landed_costs');
    }
};

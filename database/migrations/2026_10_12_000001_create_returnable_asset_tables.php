<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returnable_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('asset_code', 80);
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('serial_number')->nullable();
            $table->unsignedInteger('total_quantity')->default(1);
            $table->unsignedInteger('available_quantity')->default(1);
            $table->decimal('deposit_amount', 15, 2)->default(0);
            $table->decimal('replacement_cost', 15, 2)->default(0);
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'asset_code']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('returnable_asset_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->unsignedBigInteger('returnable_asset_id')->index();
            $table->unsignedBigInteger('parent_movement_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('movement_type', 30)->index();
            $table->string('custodian_name')->nullable();
            $table->string('custodian_phone', 60)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('returned_quantity')->default(0);
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable()->index();
            $table->date('returned_on')->nullable();
            $table->string('condition', 30)->nullable();
            $table->decimal('deposit_held', 15, 2)->default(0);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'movement_type', 'due_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returnable_asset_movements');
        Schema::dropIfExists('returnable_assets');
    }
};

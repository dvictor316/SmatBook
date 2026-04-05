<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('asset_code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->unsignedBigInteger('depreciation_account_id')->nullable()->index();
            $table->unsignedBigInteger('expense_account_id')->nullable()->index();
            $table->date('acquired_on')->nullable();
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->unsignedInteger('useful_life_months')->default(12);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->string('status', 30)->default('active')->index();
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('book_value', 15, 2)->default(0);
            $table->date('last_depreciated_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->unsignedBigInteger('fixed_asset_id')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->date('run_date');
            $table->string('period_label', 50)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('reference_no', 100)->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_depreciations');
    }
};

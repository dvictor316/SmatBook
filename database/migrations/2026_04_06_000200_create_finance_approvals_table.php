<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->string('approval_type', 40)->index();
            $table->string('approvable_type')->nullable();
            $table->unsignedBigInteger('approvable_id')->nullable();
            $table->string('reference_no')->nullable()->index();
            $table->string('title');
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_approvals');
    }
};

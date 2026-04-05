<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('branch_id')->nullable()->index();
            $table->string('branch_name')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->string('name');
            $table->string('frequency', 20)->default('monthly');
            $table->unsignedInteger('interval_value')->default(1);
            $table->date('starts_on')->nullable();
            $table->date('next_run_on')->nullable()->index();
            $table->timestamp('last_run_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->boolean('auto_post')->default(false);
            $table->boolean('approval_required')->default(false);
            $table->text('notes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};

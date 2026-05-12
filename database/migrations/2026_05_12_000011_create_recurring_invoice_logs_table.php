<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoice_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('template_id')->index();
            $table->foreign('template_id')
                  ->references('id')
                  ->on('recurring_invoice_templates')
                  ->onDelete('cascade');

            // The generated Sale (nullable on failure)
            $table->unsignedBigInteger('sale_id')->nullable()->index();

            // Idempotency key — template_id + scheduled_date must be unique
            $table->date('scheduled_date')->index();

            $table->string('status', 20)->default('success');
            // Values: success|failed|skipped

            $table->string('generated_by', 30)->default('scheduler');
            // Values: scheduler|manual|system

            $table->text('message')->nullable();

            $table->timestamps();

            // Prevent duplicate generation for the same scheduled run
            $table->unique(['template_id', 'scheduled_date'], 'unique_template_run');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_invoice_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_leads')) {
            return;
        }

        Schema::create('agent_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('state_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('business_name');
            $table->string('business_category')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('status')->default('new');
            $table->string('source')->default('manual');
            $table->string('lead_type')->default('personal');
            $table->string('priority')->default('normal');
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->unsignedInteger('invoice_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['agent_id', 'status']);
            $table->index(['agent_id', 'lead_type']);
            $table->index(['state_manager_id', 'agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_leads');
    }
};

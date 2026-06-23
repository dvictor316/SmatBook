<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('state_manager_zones')) {
            Schema::create('state_manager_zones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('state_manager_id')->constrained('users')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->decimal('target_revenue', 15, 2)->default(0);
                $table->unsignedInteger('target_customers')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['state_manager_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('agent_zone_assignments')) {
            Schema::create('agent_zone_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('state_manager_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('zone_id')->nullable()->constrained('state_manager_zones')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();

                $table->unique(['state_manager_id', 'agent_id']);
                $table->index(['zone_id', 'agent_id']);
            });
        }

        if (!Schema::hasTable('agent_violations')) {
            Schema::create('agent_violations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('state_manager_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->string('severity')->default('medium');
                $table->string('status')->default('open');
                $table->text('notes')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['state_manager_id', 'status']);
                $table->index(['agent_id', 'severity']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_violations');
        Schema::dropIfExists('agent_zone_assignments');
        Schema::dropIfExists('state_manager_zones');
    }
};

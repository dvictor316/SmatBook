<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 120)->nullable();
            $table->string('recipient', 191);
            $table->string('subject', 191)->nullable();
            $table->string('status', 40)->default('queued');
            $table->json('details')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('event_type');
            $table->index('recipient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_audit_logs');
    }
};


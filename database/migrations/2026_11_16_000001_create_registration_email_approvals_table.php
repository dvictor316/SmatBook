<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_email_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('purpose', 16);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('session_secret_hash', 64);
            $table->string('link_token_hash', 64)->unique();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_email_approvals');
    }
};

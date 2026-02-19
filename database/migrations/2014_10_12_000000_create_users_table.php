<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        
        // ADD THIS LINE HERE:
        // This allows 'admin' and sets a default so it never stays empty
        $table->enum('role', ['user', 'admin', 'editor'])->default('user');
        
        // These are for Social Login
        $table->string('provider_id')->nullable();
        $table->string('provider_name')->nullable();

        $table->rememberToken();
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
{
    Schema::create('tenants', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The owner
        $table->string('name')->nullable(); // Company Name
        $table->string('slug')->unique()->nullable(); // for subdomains if needed
        
        // PERSISTENCE COLUMNS
        $table->integer('onboarding_step')->default(1); // 1: Register, 2: Setup, 3: Payment, 4: Active
        $table->boolean('is_active')->default(false);
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

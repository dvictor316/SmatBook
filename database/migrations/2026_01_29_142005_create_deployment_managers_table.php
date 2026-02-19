<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * domain => env('SESSION_DOMAIN', null)
     */
    public function up(): void
    {
        Schema::create('deployment_managers', function (Blueprint $table) {
            $table->id();
            // Link to the main user account
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Business & Institutional Profile
            $table->string('business_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            
            // Identity Verification details
            $table->string('id_type')->nullable(); // NIN, BVN, CAC, Passport
            $table->string('id_number')->nullable();
            
            // Manager Status & Leveling
            $table->string('status')->default('pending_info'); // pending_info, active, suspended
            $table->integer('deployment_limit')->default(10); // Max companies they can deploy
            $table->decimal('commission_rate', 5, 2)->default(0.00); // For potential payouts
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployment_managers');
    }
};
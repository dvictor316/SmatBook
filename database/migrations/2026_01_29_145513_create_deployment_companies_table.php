<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('deployment_companies', function (Blueprint $table) {
        $table->id();
        // The "Who": Link to the Manager (User ID)
        $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');
        
        // The "What": Link to the newly created Company
        $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
        
        // Deployment Metadata
        $table->string('deployment_status')->default('active'); 
        $table->decimal('manager_commission', 10, 2)->default(0.00);
        $table->json('setup_config')->nullable(); // Store specific deployment settings
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployment_companies');
    }
};

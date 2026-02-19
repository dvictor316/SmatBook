<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::create('domains', function (Blueprint $table) {
        $table->id();
        $table->string('customer_name');
        $table->string('email');
        $table->string('domain_name')->unique();
        $table->integer('employees')->default(0);
        $table->string('package_name');
        $table->string('package_type')->nullable(); // Monthly/Yearly
        $table->enum('status', ['Pending', 'Active', 'Rejected'])->default('Pending');
        $table->date('expiry_date')->nullable();
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};

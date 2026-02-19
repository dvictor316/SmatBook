<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
{
    Schema::create('activities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
        $table->foreignId('user_id')->nullable()->constrained('users');
        $table->string('description');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

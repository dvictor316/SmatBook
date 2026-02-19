<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
Schema::create('events', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->dateTime('start');
    $table->dateTime('end')->nullable();
    $table->string('category_color')->default('bg-info'); // For bg-danger, bg-success etc.
    $table->foreignId('user_id')->constrained(); // To keep events private to the user
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

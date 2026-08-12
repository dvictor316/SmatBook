<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hotel_rate_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->unsignedBigInteger('room_type_id')->nullable()->index();
            $table->decimal('rate', 14, 2)->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('applicable_days')->nullable();
            $table->unsignedInteger('min_stay')->nullable();
            $table->unsignedInteger('max_stay')->nullable();
            $table->string('meal_plan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotel_rate_plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hotel_room_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->nullable()->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('bed_type')->nullable();
            $table->unsignedInteger('beds')->nullable();
            $table->unsignedInteger('max_adults')->nullable();
            $table->unsignedInteger('max_children')->nullable();
            $table->unsignedInteger('max_occupancy')->nullable();
            $table->decimal('base_rate', 14, 2)->default(0);
            $table->decimal('weekend_rate', 14, 2)->nullable();
            $table->decimal('extra_adult_charge', 14, 2)->nullable();
            $table->decimal('extra_child_charge', 14, 2)->nullable();
            $table->decimal('extra_bed_charge', 14, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotel_room_types');
    }
};

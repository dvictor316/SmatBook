<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hotel_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('room_type_id')->nullable()->index();
            $table->string('room_number');
            $table->string('floor')->nullable();
            $table->string('wing')->nullable();
            $table->decimal('base_rate_override', 14, 2)->nullable();
            $table->string('operational_status')->default('available');
            $table->string('housekeeping_status')->default('clean');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'room_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('hotel_rooms');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hotel_room_images')) {
            return;
        }

        Schema::create('hotel_room_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('room_id')->index();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->boolean('is_cover')->default(false)->index();
            $table->boolean('is_panorama')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('room_id')->references('id')->on('hotel_rooms')->cascadeOnDelete();
            $table->index(['company_id', 'room_id', 'sort_order'], 'hotel_room_images_scope_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_room_images');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hotel_room_blocks')) {
            Schema::create('hotel_room_blocks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('property_id')->index();
                $table->unsignedBigInteger('room_id')->index();
                $table->date('start_date')->index();
                $table->date('end_date')->index();
                $table->string('block_type', 30)->default('blocked')->index();
                $table->string('status', 20)->default('active')->index();
                $table->string('reason', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hotel_operational_events')) {
            Schema::create('hotel_operational_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('property_id')->nullable()->index();
                $table->unsignedBigInteger('reservation_id')->nullable()->index();
                $table->unsignedBigInteger('stay_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('room_id')->nullable()->index();
                $table->string('event_type', 80)->index();
                $table->string('title', 160);
                $table->text('description')->nullable();
                $table->json('meta')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_operational_events');
        Schema::dropIfExists('hotel_room_blocks');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_rooms', 'room_image')) {
                $table->string('room_image')->nullable()->after('base_rate_override');
            }
            if (!Schema::hasColumn('hotel_rooms', 'panorama_image')) {
                $table->string('panorama_image')->nullable()->after('room_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_rooms', 'panorama_image')) {
                $table->dropColumn('panorama_image');
            }
            if (Schema::hasColumn('hotel_rooms', 'room_image')) {
                $table->dropColumn('room_image');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_guest_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('customer_id');
            $table->string('nationality', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('id_type', 50)->nullable();
            $table->string('id_number', 100)->nullable();
            $table->string('id_issuing_country', 100)->nullable();
            $table->date('id_expiry_date')->nullable();
            $table->text('preferences')->nullable();
            $table->text('allergies')->nullable();
            $table->string('loyalty_number', 80)->nullable();
            $table->string('vip_status', 30)->default('standard');
            $table->boolean('do_not_rent')->default(false)->index();
            $table->text('do_not_rent_reason')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['company_id', 'customer_id']);
        });

        Schema::create('hotel_staff_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->date('shift_date')->index();
            $table->string('department', 50)->index();
            $table->string('shift_name', 80);
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('status', 20)->default('scheduled')->index();
            $table->decimal('opening_float', 14, 2)->default(0);
            $table->decimal('closing_cash', 14, 2)->nullable();
            $table->text('handover_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['property_id', 'user_id', 'shift_date', 'starts_at'], 'hotel_staff_shift_unique');
        });

        Schema::create('hotel_rate_restrictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('rate_plan_id')->index();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->string('applicable_days', 30)->nullable();
            $table->unsignedInteger('min_stay')->nullable();
            $table->unsignedInteger('max_stay')->nullable();
            $table->boolean('closed_to_arrival')->default(false);
            $table->boolean('closed_to_departure')->default(false);
            $table->boolean('stop_sell')->default(false)->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_rate_restrictions');
        Schema::dropIfExists('hotel_staff_shifts');
        Schema::dropIfExists('hotel_guest_profiles');
    }
};

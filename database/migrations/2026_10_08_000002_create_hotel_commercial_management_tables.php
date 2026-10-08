<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_booking_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->nullable()->index();
            $table->string('code', 40);
            $table->string('name');
            $table->string('source_type', 30)->default('direct')->index();
            $table->string('commission_type', 20)->default('none');
            $table->decimal('commission_value', 10, 2)->default(0);
            $table->unsignedInteger('settlement_days')->default(0);
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 60)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('hotel_corporate_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('account_code', 40);
            $table->string('company_name');
            $table->string('tax_id', 80)->nullable();
            $table->string('billing_contact')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone', 60)->nullable();
            $table->text('billing_address')->nullable();
            $table->decimal('credit_limit', 16, 2)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->decimal('negotiated_discount_percent', 8, 2)->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['company_id', 'account_code']);
        });

        Schema::create('hotel_group_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('corporate_account_id')->nullable()->index();
            $table->unsignedBigInteger('booking_source_id')->nullable()->index();
            $table->unsignedBigInteger('primary_customer_id')->nullable()->index();
            $table->string('group_code', 50);
            $table->string('group_name');
            $table->date('arrival_date')->index();
            $table->date('departure_date')->index();
            $table->unsignedInteger('rooms_requested')->default(1);
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->decimal('estimated_total', 16, 2)->default(0);
            $table->decimal('deposit_required', 16, 2)->default(0);
            $table->decimal('deposit_received', 16, 2)->default(0);
            $table->date('release_date')->nullable();
            $table->string('status', 25)->default('tentative')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['company_id', 'group_code']);
        });

        Schema::create('hotel_group_room_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('group_booking_id')->index();
            $table->unsignedBigInteger('room_type_id')->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->string('guest_name')->nullable();
            $table->unsignedInteger('rooms')->default(1);
            $table->decimal('nightly_rate', 14, 2)->default(0);
            $table->string('status', 25)->default('blocked')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('hotel_deposit_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->unsignedBigInteger('group_booking_id')->nullable()->index();
            $table->unsignedBigInteger('corporate_account_id')->nullable()->index();
            $table->unsignedBigInteger('folio_id')->nullable()->index();
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->date('transaction_date')->index();
            $table->string('transaction_type', 20)->default('receipt')->index();
            $table->string('payment_method', 30)->default('cash');
            $table->decimal('amount', 16, 2);
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('posted')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_source_id')->nullable()->index();
            $table->unsignedBigInteger('corporate_account_id')->nullable()->index();
            $table->unsignedBigInteger('group_booking_id')->nullable()->index();
        });

        Schema::table('guest_folios', function (Blueprint $table) {
            $table->unsignedBigInteger('corporate_account_id')->nullable()->index();
            $table->unsignedBigInteger('group_booking_id')->nullable()->index();
            $table->date('due_date')->nullable()->index();
            $table->string('folio_label', 80)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('guest_folios', fn (Blueprint $table) => $table->dropColumn(['corporate_account_id', 'group_booking_id', 'due_date', 'folio_label']));
        Schema::table('reservations', fn (Blueprint $table) => $table->dropColumn(['booking_source_id', 'corporate_account_id', 'group_booking_id']));
        Schema::dropIfExists('hotel_deposit_transactions');
        Schema::dropIfExists('hotel_group_room_allocations');
        Schema::dropIfExists('hotel_group_bookings');
        Schema::dropIfExists('hotel_corporate_accounts');
        Schema::dropIfExists('hotel_booking_sources');
    }
};

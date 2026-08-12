<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('room_id')->nullable()->index();
            $table->dateTime('checkin_at')->nullable();
            $table->dateTime('expected_checkout_at')->nullable();
            $table->dateTime('actual_checkout_at')->nullable();
            $table->decimal('agreed_rate', 14, 2)->default(0);
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->string('status')->default('checked_in')->index();
            $table->unsignedBigInteger('checked_in_by')->nullable()->index();
            $table->unsignedBigInteger('checked_out_by')->nullable()->index();
            $table->timestamps();

            $table->index(['company_id','property_id','room_id','status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stays');
    }
};

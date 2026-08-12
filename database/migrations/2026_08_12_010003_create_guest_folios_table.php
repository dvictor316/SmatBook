<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('guest_folios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('stay_id')->nullable()->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('folio_number')->index();
            $table->decimal('opening_deposit', 14, 2)->default(0);
            $table->decimal('total_charges', 14, 2)->default(0);
            $table->decimal('total_payments', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->default(0)->index();
            $table->string('status')->default('open')->index();
            $table->timestamps();

            $table->unique(['company_id','folio_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('guest_folios');
    }
};

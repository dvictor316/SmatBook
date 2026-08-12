<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('folio_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->unsignedBigInteger('folio_id')->index();
            $table->unsignedBigInteger('stay_id')->nullable()->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->string('description');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('type')->default('charge')->index();
            $table->unsignedBigInteger('posted_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('folio_items');
    }
};

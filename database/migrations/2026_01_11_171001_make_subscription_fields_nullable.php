<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::table('subscriptions', function (Blueprint $table) {
        // Change these to nullable so the initial 'insert' works without them
        $table->string('domain_prefix')->nullable()->change();
        $table->string('subscriber_name')->nullable()->change();
        $table->string('employee_size')->nullable()->change();
        $table->unsignedBigInteger('plan_id')->nullable()->change();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            //
        });
    }
};

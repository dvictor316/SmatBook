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
    Schema::table('sales', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->string('order_number')->after('id')->unique();
    });
}

public function down(): void
{
    Schema::table('sales', function (Illuminate\Database\Schema\Blueprint $table) {
        $table->dropColumn('order_number');
    });
}
};

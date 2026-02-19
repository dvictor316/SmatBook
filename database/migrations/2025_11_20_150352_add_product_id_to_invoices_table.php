<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->unsignedBigInteger('product_id')->nullable(); // Add product_id column
        $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade'); // Create foreign key constraint
    });
}

public function down()
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->dropForeign(['product_id']); // Drop foreign key constraint
        $table->dropColumn('product_id'); // Remove product_id column
    });
}

};

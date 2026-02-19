<?php
// database/migrations/..._update_quantity_column_on_sale_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateQuantityColumnOnSaleItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // This ensures the quantity cannot be NULL and defaults to 1 if nothing is provided
            $table->integer('quantity')->unsigned()->default(1)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Optional: revert if you need to roll back
            // $table->integer('quantity')->nullable()->change(); 
        });
    }
}

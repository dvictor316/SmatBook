<?php

// database/migrations/..._update_total_price_column_on_sale_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTotalPriceColumnOnSaleItemsTable extends Migration
{
    public function up()
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Ensures the total_price cannot be NULL and defaults to 0.00 if nothing is provided
            $table->decimal('total_price', 10, 2)->default(0.00)->nullable(false)->change();
        });
    }

    public function down()
    {
        // Optional: revert if you need to roll back
    }
}

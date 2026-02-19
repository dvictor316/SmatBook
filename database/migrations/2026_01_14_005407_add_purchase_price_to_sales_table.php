<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    Schema::table('sales', function (Blueprint $table) {
        // We use decimal for money to keep it accurate
        $table->decimal('purchase_price', 15, 2)->default(0)->after('total');
    });
}

public function down()
{
    Schema::table('sales', function (Blueprint $table) {
        $table->dropColumn('purchase_price');
    });
}
};

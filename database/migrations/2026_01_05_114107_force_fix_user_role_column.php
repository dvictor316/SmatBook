<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
{
    Schema::table('users', function ($table) {
        // Changing it to string removes the strict "List" restriction
        $table->string('role')->default('user')->change();
    });
}

public function down()
{
    Schema::table('users', function ($table) {
        $table->string('role')->default('user')->change();
    });
}
};

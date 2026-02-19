<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
{
    Schema::table('companies', function (Blueprint $table) {
        $table->string('email')->nullable()->change();
    });
}

public function down()
{
    // Fix: If you are rolling back, don't force NOT NULL if you have null data.
    // Or, keep it nullable in the down method as well to avoid the crash.
    Schema::table('companies', function (Blueprint $table) {
        $table->string('email')->nullable()->change(); 
    });
}
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('companies', function (Blueprint $table) {
        // Add user_id after the 'id' column
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')->after('id');
    });
}

public function down()
{
    Schema::table('companies', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropColumn('user_id');
    });
}
};

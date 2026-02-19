<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
{
    Schema::table('categories', function (Blueprint $table) {
        $table->string('image')->nullable()->after('name');
        $table->boolean('status')->default(1)->after('description'); // 1 = Active, 0 = Inactive
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            //
        });
    }
};

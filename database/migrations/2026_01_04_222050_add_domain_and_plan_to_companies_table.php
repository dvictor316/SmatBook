<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
public function up()
{
    Schema::table('companies', function (Blueprint $table) {
        // Only add 'domain' if it doesn't exist
        if (!Schema::hasColumn('companies', 'domain')) {
            $table->string('domain')->nullable()->after('name');
        }
        
        // Only add 'plan' if it doesn't exist
        if (!Schema::hasColumn('companies', 'plan')) {
            $table->string('plan')->notNull()->default('basic')->after('domain');
        }
    });
}

public function down()
{
    Schema::table('companies', function (Blueprint $table) {
        if (Schema::hasColumn('companies', 'domain')) {
            $table->dropColumn('domain');
        }
        if (Schema::hasColumn('companies', 'plan')) {
            $table->dropColumn('plan');
        }
    });
}
};

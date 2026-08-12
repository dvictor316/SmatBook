<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('companies', 'industry')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('industry')->nullable();
                $table->index('industry');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'industry')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropIndex(['industry']);
                $table->dropColumn('industry');
            });
        }
    }
};

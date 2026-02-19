<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    // Only try to rename if the old 'domain' column still exists
    if (Schema::hasColumn('domains', 'domain')) {
        Schema::table('domains', function (Blueprint $table) {
            $table->renameColumn('domain', 'domain_name');
        });
    }
}

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->renameColumn('domain_name', 'domain');
        });
    }
};
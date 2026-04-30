<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assets') || Schema::hasColumn('assets', 'asset_name')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->string('asset_name')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('assets') || !Schema::hasColumn('assets', 'asset_name')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('asset_name');
        });
    }
};

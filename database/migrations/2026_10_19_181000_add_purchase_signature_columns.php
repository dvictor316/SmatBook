<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'signature_name')) {
                $table->string('signature_name', 100)->nullable();
            }
            if (!Schema::hasColumn('purchases', 'signature_image')) {
                $table->string('signature_image')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Preserve signatures on historical purchase documents during a code rollback.
    }
};

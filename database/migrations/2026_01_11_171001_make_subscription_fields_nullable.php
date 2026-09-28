<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        $nullableColumns = [
            'domain_prefix' => fn (Blueprint $table) => $table->string('domain_prefix')->nullable()->change(),
            'subscriber_name' => fn (Blueprint $table) => $table->string('subscriber_name')->nullable()->change(),
            'employee_size' => fn (Blueprint $table) => $table->integer('employee_size')->nullable()->change(),
            'plan_id' => fn (Blueprint $table) => $table->unsignedBigInteger('plan_id')->nullable()->change(),
        ];

        foreach ($nullableColumns as $column => $change) {
            if (Schema::hasColumn('subscriptions', $column)) {
                Schema::table('subscriptions', $change);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            //
        });
    }
};

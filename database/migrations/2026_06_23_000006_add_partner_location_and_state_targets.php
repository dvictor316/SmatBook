<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable();
            }
            if (!Schema::hasColumn('users', 'state_region')) {
                $table->string('state_region')->nullable()->after('country');
            }
            if (!Schema::hasColumn('users', 'local_council')) {
                $table->string('local_council')->nullable()->after('state_region');
            }
            if (!Schema::hasColumn('users', 'state_manager_id')) {
                $table->foreignId('state_manager_id')->nullable()->after('company_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('deployment_managers', function (Blueprint $table) {
            if (!Schema::hasColumn('deployment_managers', 'country')) {
                $table->string('country')->nullable()->after('address');
            }
            if (!Schema::hasColumn('deployment_managers', 'state_region')) {
                $table->string('state_region')->nullable()->after('country');
            }
            if (!Schema::hasColumn('deployment_managers', 'local_council')) {
                $table->string('local_council')->nullable()->after('state_region');
            }
            if (!Schema::hasColumn('deployment_managers', 'state_revenue_target')) {
                $table->decimal('state_revenue_target', 15, 2)->default(0)->after('deployment_limit');
            }
            if (!Schema::hasColumn('deployment_managers', 'state_customer_target')) {
                $table->unsignedInteger('state_customer_target')->default(0)->after('state_revenue_target');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['state_manager_id', 'local_council', 'state_region', 'country'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    if ($column === 'state_manager_id') {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        Schema::table('deployment_managers', function (Blueprint $table) {
            foreach (['state_customer_target', 'state_revenue_target', 'local_council', 'state_region', 'country'] as $column) {
                if (Schema::hasColumn('deployment_managers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

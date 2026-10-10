<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'email_audit_logs',
        'exchange_rates',
        'cheques',
        'loans',
        'purchase_requisitions',
        'request_for_quotations',
        'goods_received_notes',
        'product_lots',
        'serial_numbers',
        'bill_of_materials',
        'manufacturing_orders',
        'asset_maintenance_logs',
        'departments',
        'cost_centers',
        'intercompany_transactions',
        'leave_requests',
        'attendance_records',
        'timesheets',
        'forecasts',
        'landed_costs',
        'hotel_properties',
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `branch_id` VARCHAR(64) NULL");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"branch_id\" TYPE VARCHAR(64) USING \"branch_id\"::varchar");
            }
        }
    }

    public function down(): void
    {
        // UUID branch identifiers cannot be safely converted back to integers.
    }
};

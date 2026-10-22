<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesReportingDate
{
    public static function expression(string $table = 'sales'): string
    {
        if (! preg_match('/^[a-z_]+$/', $table)) throw new \InvalidArgumentException('Invalid sales table.');
        $fallback = self::timestampExpression($table.'.created_at');
        return Schema::hasColumn('sales', 'order_date')
            ? "COALESCE(DATE({$table}.order_date), {$fallback})"
            : $fallback;
    }
    public static function timestampExpression(string $column): string
    {
        if (! preg_match('/^[a-z_]+\.[a-z_]+$/', $column)) throw new \InvalidArgumentException('Invalid reporting timestamp.');
        $instant = ReportingPeriod::today();
        $seconds = $instant->getOffset() - $instant->setTimezone(config('app.timezone', 'UTC'))->getOffset();
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "DATE({$column}, '{$seconds} seconds')",
            'pgsql' => "CAST({$column} + INTERVAL '{$seconds} seconds' AS DATE)",
            default => "DATE(DATE_ADD({$column}, INTERVAL {$seconds} SECOND))",
        };
    }

}

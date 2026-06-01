<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetEmptyTableSequences extends Command
{
    protected $signature = 'database:reset-empty-sequences {--force : Run without confirmation}';

    protected $description = 'Reset AUTO_INCREMENT counters only for tables that are currently empty.';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('Reset AUTO_INCREMENT counters for empty tables only?')) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $tables = collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
                ->map(fn ($row) => (string) (array_values((array) $row)[0] ?? ''))
                ->filter()
                ->values();

            $reset = 0;
            foreach ($tables as $table) {
                if ((int) DB::table($table)->count() !== 0) {
                    continue;
                }

                DB::statement('ALTER TABLE `' . str_replace('`', '``', $table) . '` AUTO_INCREMENT = 1');
                $reset++;
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info("Reset AUTO_INCREMENT on {$reset} empty table(s).");

        return self::SUCCESS;
    }
}

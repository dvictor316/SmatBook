<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DropAllTables extends Command
{
    protected $signature = 'db:dropall';
    protected $description = 'Drop all tables in the database';

    public function handle()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        $tables = DB::select('SHOW TABLES');
        $dbName = env('DB_DATABASE');
        $key = "Tables_in_$dbName";

        foreach ($tables as $table) {
            $tableName = $table->$key;
            DB::statement("DROP TABLE IF EXISTS `$tableName`;");
            $this->info("Dropped table: $tableName");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $this->info('All tables dropped successfully.');
    }
}

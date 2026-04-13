<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans') || ! Schema::hasColumn('plans', 'user_limit')) {
            return;
        }

        DB::table('plans')
            ->whereRaw('LOWER(name) like ?', ['%basic%'])
            ->whereRaw('LOWER(name) like ?', ['%solo%'])
            ->update(['user_limit' => 1]);

        DB::table('plans')
            ->whereRaw('LOWER(name) like ?', ['%basic%'])
            ->whereRaw('LOWER(name) not like ?', ['%solo%'])
            ->update(['user_limit' => 3]);

        DB::table('plans')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) like ?', ['%pro%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%professional%']);
            })
            ->whereRaw('LOWER(name) like ?', ['%solo%'])
            ->update(['user_limit' => 2]);

        DB::table('plans')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) like ?', ['%pro%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%professional%']);
            })
            ->whereRaw('LOWER(name) not like ?', ['%solo%'])
            ->update(['user_limit' => 5]);

        DB::table('plans')
            ->whereRaw('LOWER(name) like ?', ['%enterprise%'])
            ->whereRaw('LOWER(name) like ?', ['%solo%'])
            ->update(['user_limit' => 3]);

        DB::table('plans')
            ->whereRaw('LOWER(name) like ?', ['%enterprise%'])
            ->whereRaw('LOWER(name) not like ?', ['%solo%'])
            ->update(['user_limit' => 8]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('plans') || ! Schema::hasColumn('plans', 'user_limit')) {
            return;
        }

        DB::table('plans')
            ->whereRaw('LOWER(name) like ?', ['%basic%'])
            ->whereRaw('LOWER(name) like ?', ['%solo%'])
            ->update(['user_limit' => 1]);

        DB::table('plans')
            ->whereRaw('LOWER(name) like ?', ['%basic%'])
            ->whereRaw('LOWER(name) not like ?', ['%solo%'])
            ->update(['user_limit' => 2]);

        DB::table('plans')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) like ?', ['%pro%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%professional%']);
            })
            ->whereRaw('LOWER(name) like ?', ['%solo%'])
            ->update(['user_limit' => 1]);

        DB::table('plans')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) like ?', ['%pro%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%professional%']);
            })
            ->whereRaw('LOWER(name) not like ?', ['%solo%'])
            ->update(['user_limit' => 3]);

        DB::table('plans')
            ->whereRaw('LOWER(name) like ?', ['%enterprise%'])
            ->whereRaw('LOWER(name) like ?', ['%solo%'])
            ->update(['user_limit' => 1]);

        DB::table('plans')
            ->whereRaw('LOWER(name) like ?', ['%enterprise%'])
            ->whereRaw('LOWER(name) not like ?', ['%solo%'])
            ->update(['user_limit' => null]);
    }
};

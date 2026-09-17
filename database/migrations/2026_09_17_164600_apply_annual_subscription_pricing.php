<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        DB::table('plans')->whereRaw('LOWER(billing_cycle) = ?', ['monthly'])->update([
            'is_active' => 0,
            'status' => 'inactive',
        ]);

        DB::table('plans')->whereIn('name', ['Starter Solo Yearly', 'Basic Solo Yearly'])->update([
            'is_active' => 0,
            'status' => 'inactive',
        ]);

        $plans = [
            'Starter Yearly' => [20000, 1, 0],
            'Basic Yearly' => [80000, 2, 0],
            'Pro Solo Yearly' => [100000, 3, 0],
            'Pro Yearly' => [150000, 5, 1],
            'Enterprise Solo Yearly' => [200000, 4, 0],
            'Enterprise Yearly' => [300000, 10, 0],
        ];

        foreach ($plans as $name => [$price, $userLimit, $recommended]) {
            DB::table('plans')->where('name', $name)->whereRaw('LOWER(billing_cycle) = ?', ['yearly'])->update([
                'price' => $price,
                'user_limit' => $userLimit,
                'recommended' => $recommended,
                'is_active' => 1,
                'status' => 'active',
            ]);
        }
    }

    public function down(): void
    {
        // Pricing changes are intentionally not rolled back on a live billing system.
    }
};

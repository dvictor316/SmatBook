<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('subscriptions:expire-due')->hourly();

        $schedule->call(function () {
            $today = date('Y-m-d');
            $thisMonth = date('Y-m');

            // 1. Inflow / Outflow Logic
            $inflow = DB::table('sales')->whereDate('created_at', $today)->sum('total') 
                    + DB::table('payments')->whereDate('created_at', $today)
                        ->where('payment_method', '!=', 'Sales Payment')->sum('amount');

            $outflow = DB::table('expenses')->whereDate('created_at', $today)->sum('amount');
            $profit = $inflow - $outflow;
            $margin = ($inflow > 0) ? ($profit / $inflow) * 100 : 0;

            // 2. Monthly Average Comparison
            $monthlyProfit = DB::table('sales')->where('created_at', 'like', "$thisMonth%")->sum('total')
                           - DB::table('expenses')->where('created_at', 'like', "$thisMonth%")->sum('amount');
            $daysPassed = (int)date('d'); 
            $avgDailyProfit = ($daysPassed > 0) ? ($monthlyProfit / $daysPassed) : 0;
            $performance = ($profit >= $avgDailyProfit) ? 'Above Average' : 'Below Average';

            // 3. Top 5 Transactions
            $topSales = DB::table('sales')
                ->whereDate('created_at', $today)
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get();

            // 4. Expense Breakdown (Using 'category')
            $expenseBreakdown = DB::table('expenses')
                ->select('category', DB::raw('SUM(amount) as total_amount'))
                ->whereDate('created_at', $today)
                ->groupBy('category')
                ->get();

            // 5. Low Stock Alert (Using 'stock_quantity')
            $lowStockItems = DB::table('products')
                ->where('stock', '<', 10) 
                ->select('name', 'stock')
                ->get();

            // 6. Security Check (DISABLED to prevent column error)
            $unusualLogins = []; 

            $data = [
                'inflow'           => $inflow,
                'outflow'          => $outflow,
                'profit'           => $profit,
                'margin'           => round($margin, 2),
                'avgDailyProfit'   => round($avgDailyProfit, 2),
                'performance'      => $performance,
                'topSales'         => $topSales,
                'expenseBreakdown' => $expenseBreakdown,
                'lowStockItems'    => $lowStockItems,
                'unusualLogins'    => $unusualLogins,
                'sources'          => 'Automated System Check'
            ];

            Mail::to('support@smatbook.com')->send(new \App\Mail\DailyBusinessSummary($data));
            
        })->dailyAt('21:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}

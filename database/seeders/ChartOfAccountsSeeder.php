<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ASSETS (Cash & Bank)
            ['name' => 'Main Bank Account', 'code' => '1001', 'type' => 'Asset'],
            ['name' => 'Petty Cash', 'code' => '1002', 'type' => 'Asset'],
            
            // EXPENSES (Costs)
            ['name' => 'Office Supplies', 'code' => '5001', 'type' => 'Expense'],
            ['name' => 'Rent Expense', 'code' => '5002', 'type' => 'Expense'],
            ['name' => 'Utilities (Electricity/Water)', 'code' => '5003', 'type' => 'Expense'],
            ['name' => 'Salaries & Wages', 'code' => '5004', 'type' => 'Expense'],
            ['name' => 'Marketing & Ads', 'code' => '5005', 'type' => 'Expense'],
            
            // REVENUE (Income)
            ['name' => 'Sales Revenue', 'code' => '4001', 'type' => 'Revenue'],
            ['name' => 'Service Income', 'code' => '4002', 'type' => 'Revenue'],

            // LIABILITIES & EQUITY
            ['name' => 'Accounts Payable', 'code' => '2001', 'type' => 'Liability'],
            ['name' => 'Owner Equity', 'code' => '3001', 'type' => 'Equity'],
        ];

        foreach ($accounts as $account) {
            Account::updateOrCreate(['code' => $account['code']], $account);
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon; // Import Carbon for date handling

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        // Find or create a default customer
        $customer = Customer::firstOrCreate(
            ['email' => 'victor@example.com'],
            ['name' => 'Victor Oyzzy', 'phone' => '08012345678', 'address' => '123 Customer Address, Enugu']
        );

        // Insert Invoices
        Invoice::create([
            'customer_id' => $customer->id,
            'company_id' => 1,
            'amount' => 5000.00,
            'status' => 'Unpaid',
            'due_date' => Carbon::now()->addDays(14),
            'invoice_date' => Carbon::now(),
        ]);

        Invoice::create([
            'customer_id' => $customer->id,
            'company_id' => 1,
            'amount' => 2000.00,
            'status' => 'Overdue',
            'due_date' => Carbon::now()->subDays(1),
            'invoice_date' => Carbon::now()->subDays(14),
        ]);

        Invoice::create([
            'customer_id' => $customer->id,
            'company_id' => 1,
            'amount' => 8000.00,
            'status' => 'Paid',
            'due_date' => Carbon::now()->addDays(30),
            'invoice_date' => Carbon::now(),
        ]);
    }
}

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
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // Find or create a default customer
        $customer = Customer::firstOrCreate(
            ['email' => 'sample.customer@example.com'],
            ['name' => 'Sample Customer', 'phone' => '08000000000', 'address' => 'Sample Address']
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

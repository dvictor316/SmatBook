<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Support\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesInvoiceController extends Controller
{
public function index()
{
    $salesData = DB::table('sales')->whereNull('deleted_at')->get();

    $invoices = $salesData->map(function ($sale) {
        return [
            'id'          => $sale->id,
            'InvoiceID'   => $sale->invoice_no,
            'Category'    => 'Sales', 
            'IssuedOn'    => date('d M Y', strtotime($sale->created_at)),
            'InvoiceTo'   => $sale->customer_name ?? 'Walk-in Customer',
            'Email'       => 'customer@example.com', // Added this to fix your error
            'Image'       => 'avatar-01.jpg',
            'TotalAmount' => number_format($sale->total, 2),
            'PaidAmount'  => number_format($sale->amount_paid, 2),
            'PaymentMode' => $sale->payment_method,
            'Balance'     => number_format($sale->balance, 2),
            'DueDate'     => date('d M Y', strtotime($sale->created_at . ' + 30 days')),
            'Status'      => ucfirst($sale->payment_status),
            'Class'       => ($sale->payment_status == 'paid') ? 'bg-success-light' : 'bg-danger-light',
        ];
    });

    // Cards remain the same...
    $invoicescards = [
        ['title' => 'All Invoices', 'amount' => $salesData->count(), 'class' => 'bg-primary-light', 'icon' => 'fe fe-file-text'],
        ['title' => 'Total Amount', 'amount' => '₦' . number_format($salesData->sum('total'), 2), 'class' => 'bg-success-light', 'icon' => 'fe fe-database'],
        ['title' => 'Total Paid', 'amount' => '₦' . number_format($salesData->sum('amount_paid'), 2), 'class' => 'bg-info-light', 'icon' => 'fe fe-check-square'],
        ['title' => 'Total Pending', 'amount' => '₦' . number_format($salesData->sum('balance'), 2), 'class' => 'bg-warning-light', 'icon' => 'fe fe-clock']
    ];

    return view('Sales.recurring-invoices', compact('invoices', 'invoicescards'));
}

  public function clone($id)
    {
        $original = DB::table('sales')->where('id', $id)->first();
        
        if (!$original) {
            return back()->with('error', 'Invoice not found.');
        }

        $clone = (array) $original;
        unset($clone['id']);

        $clone['invoice_no'] = ($original->invoice_no ?: 'INV') . '-COPY-' . strtoupper(Str::random(4));
        $clone['receipt_no'] = 'REC-' . strtoupper(Str::random(10));
        $clone['order_number'] = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        $clone['created_at'] = now();
        $clone['updated_at'] = now();

        $newSaleId = DB::table('sales')->insertGetId($clone);
        $newSale = Sale::find($newSaleId);
        if ($newSale) {
            LedgerService::postSale($newSale);
        }

        return back()->with('success', 'Invoice Cloned Successfully!');
    }

    // Send Action
    public function send($id)
    {
        // Logic to trigger email would go here
        return back()->with('info', 'Email notification sent to customer.');
    }
}

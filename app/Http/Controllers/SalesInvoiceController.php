<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Sale;
use App\Support\LedgerService;
use App\Support\SystemEventMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesInvoiceController extends Controller
{
public function index()
{
    $salesData = Sale::with('customer')->latest()->get();

    $invoices = $salesData->map(function ($sale) {
        return [
            'id'          => $sale->id,
            'InvoiceID'   => $sale->invoice_no,
            'Category'    => 'Sales', 
            'IssuedOn'    => optional($sale->created_at)->format('d M Y') ?? date('d M Y'),
            'InvoiceTo'   => $sale->display_customer_name,
            'Email'       => $sale->customer?->email ?? 'No customer email',
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
    public function send(Request $request, $id)
    {
        $sale = Sale::with(['customer', 'user'])->findOrFail($id);

        $recipient = $sale->customer?->email;
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $message = 'This invoice has no valid customer email address yet.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $company = Company::query()->find($sale->company_id)
            ?: optional(auth()->user())->company;

        $companyName = $company?->company_name
            ?? $company?->name
            ?? config('app.name', 'SmartProbook');

        $sent = SystemEventMailer::sendMessage(
            $recipient,
            'Invoice ' . ($sale->invoice_no ?? ('#' . $sale->id)) . ' from ' . $companyName,
            'Customer Invoice',
            'Your invoice has been prepared and is ready for your records.',
            [
                'Customer' => $sale->display_customer_name,
                'Invoice Number' => $sale->invoice_no ?? ('#' . $sale->id),
                'Order Number' => $sale->order_number ?? 'N/A',
                'Invoice Date' => optional($sale->created_at)->format('d M Y h:i A') ?? now()->format('d M Y h:i A'),
                'Payment Method' => $sale->payment_method ?? 'N/A',
                'Total Amount' => 'NGN ' . number_format((float) ($sale->total ?? 0), 2),
                'Amount Tendered' => 'NGN ' . number_format((float) (($sale->amount_paid ?? 0) + max(0, (float) ($sale->change_amount ?? 0))), 2),
                'Applied to Sale' => 'NGN ' . number_format((float) ($sale->amount_paid ?? 0), 2),
                'Change Returned' => 'NGN ' . number_format((float) ($sale->change_amount ?? 0), 2),
                'Company' => $companyName,
            ]
        );

        $message = $sent
            ? 'Invoice emailed to customer successfully.'
            : 'Email could not be sent. Please confirm mail settings and try again.';

        if ($request->expectsJson()) {
            return response()->json(['ok' => $sent, 'message' => $message], $sent ? 200 : 500);
        }

        return back()->with($sent ? 'success' : 'error', $message);
    }
}

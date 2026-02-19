<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Signature;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices with statistics and company branding.
     */
    public function index()
    {
        return $this->renderInvoiceView();
    }

    public function invoices()
    {
        return $this->renderInvoiceView();
    }

    /**
     * Centralized logic for rendering invoice views across different filters.
     */
    private function renderInvoiceView($statusFilter = null)
    {
        $query = Sale::with('customer')->latest();
        
        if ($statusFilter) {
            $query->where('payment_status', strtolower($statusFilter));
        }

        $invoices = $query->get();
        $invoicescards = $this->getInvoiceStats();
        
        $latestSale = Sale::latest()->first();
        $logo = ($latestSale && isset($latestSale->company_logo)) ? asset('storage/' . $latestSale->company_logo) : null;

        return view('Sales.Invoices.invoices', compact('invoices', 'invoicescards', 'logo'));
    }

    /**
     * Calculate Summary Statistics for Dashboard Cards.
     */
    private function getInvoiceStats()
    {
        return [
            [
                'title' => 'All Invoices',
                'amount' => Sale::sum('total'), 
                'count' => Sale::count(),
                'icon' => 'file-text',
                'class' => 'bg-primary-light'
            ],
            [
                'title' => 'Paid Invoices',
                'amount' => Sale::where('payment_status', 'paid')->sum('total'),
                'count' => Sale::where('payment_status', 'paid')->count(),
                'icon' => 'check-square',
                'class' => 'bg-success-light'
            ],
            [
                'title' => 'Unpaid Invoices',
                'amount' => Sale::where('payment_status', 'unpaid')->sum('total'),
                'count' => Sale::where('payment_status', 'unpaid')->count(),
                'icon' => 'clock',
                'class' => 'bg-warning-light'
            ],
            [
                'title' => 'Total Received',
                'amount' => Sale::sum('amount_paid'), 
                'count' => Sale::where('amount_paid', '>', 0)->count(),
                'icon' => 'dollar-sign',
                'class' => 'bg-info-light'
            ]
        ];
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create()
    {
        $customers = Customer::all();
        $products = Product::all();
        return view('Sales.Invoices.create-invoices', compact('customers', 'products'));
    }

    public function add_invoice()
    {
        return $this->create();
    }

    /**
     * Store a newly created invoice and its items.
     */
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'customer_id' => 'required',
            'invoice_date' => 'required',
            'due_date' => 'required',
            'items' => 'required|array|min:1',
            'total_amount' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();

            $invoiceDate = $request->invoice_date ? Carbon::parse($request->invoice_date)->toDateString() : now()->toDateString();
            $dueDate = Carbon::parse($request->due_date)->toDateString();

            // 2. Create the main Invoice record
            $invoice = Invoice::create([
                'customer_id'  => $request->customer_id,
                'invoice_date' => $invoiceDate,
                'due_date'     => $dueDate,
                'status'       => $request->status ?? 'Unpaid',
                'description'  => $request->description,
                'expenses'     => $request->expenses ?? 0,
                'amount'       => $request->total_amount,
                'total'        => $request->total_amount,
                'total_amount' => $request->total_amount,
            ]);

            // 3. Save line items only when invoice_items table/model exists.
            $invoiceItemModel = '\\App\\Models\\InvoiceItem';
            if (class_exists($invoiceItemModel) && Schema::hasTable('invoice_items')) {
                foreach ($request->items as $item) {
                    if (!empty($item['product_id']) || !empty($item['name'])) {
                        $invoiceItemModel::create([
                            'invoice_id' => $invoice->id,
                            'product_id' => $item['product_id'] ?? null,
                            'name'       => $item['name'] ?? 'Product',
                            'quantity'   => $item['qty'],
                            'rate'       => $item['rate'],
                            'discount'   => $item['discount'] ?? 0,
                            'tax'        => $item['tax'] ?? 0,
                            'amount'     => $item['amount'] ?? ($item['qty'] * $item['rate']),
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('invoices.index')->with('success', 'Invoice Created Successfully');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }


    
    /**
     * Edit and Update Logic.
     */
    public function edit($id)
    {
        $invoice = Sale::findOrFail($id);
        $customers = Customer::all();
        return view('Sales.Invoices.edit', compact('invoice', 'customers'));
    }

    public function edit_invoice($id)
    {
        return $this->edit($id);
    }

    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);
        
        $request->validate([
            'customer_name'  => 'required',
            'total'          => 'required|numeric',
            'payment_status' => 'required'
        ]);

        $sale->update([
            'customer_name'  => $request->customer_name,
            'total'          => $request->total,
            'tax'            => $request->tax ?? 0,
            'payment_status' => strtolower($request->payment_status),
            'amount_paid'    => $request->amount_paid ?? $sale->amount_paid,
        ]);

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully');
    }

    /**
     * Detailed Views.
     */
    public function show($id)
    {
        $sale = Sale::with(['customer', 'items.product'])->findOrFail($id);
        return view('Sales.Invoices.invoice-details-admin', compact('sale'));
    }

    public function invoice_details_admin($id)
    {
        return $this->show($id);
    }

    public function invoice_details($id)
    {
        $sale = Sale::with(['customer', 'items.product'])->findOrFail($id);
        return view('Sales.Invoices.invoice-details', compact('sale'));
    }

    /**
     * Status Filter Shortcuts.
     */
    public function invoices_paid()      { return $this->renderInvoiceView('paid'); }
    public function invoices_unpaid()    { return $this->renderInvoiceView('unpaid'); }
    public function invoices_cancelled() { return $this->renderInvoiceView('cancelled'); }
    public function invoices_draft()     { return $this->renderInvoiceView('draft'); }
    public function invoices_overdue()   { return $this->renderInvoiceView('overdue'); }
    public function invoices_recurring() { return $this->renderInvoiceView('recurring'); }
    public function invoices_refunded()  { return $this->renderInvoiceView('refunded'); }

    /**
     * Recurring Invoices Management.
     */
    public function recurringInvoices()
    {
        $sales = DB::table('sales')->whereNull('deleted_at')->get();

        $invoices = $sales->map(function ($sale) {
            return [
                'InvoiceID'   => $sale->invoice_no,
                'Category'    => 'Sales',
                'IssuedOn'    => date('d M Y', strtotime($sale->created_at)),
                'InvoiceTo'   => $sale->customer_name,
                'Image'       => 'avatar-01.jpg',
                'Email'       => 'customer@example.com',
                'TotalAmount' => '₦' . number_format($sale->total, 2),
                'PaidAmount'  => '₦' . number_format($sale->paid ?? 0, 2),
                'PaymentMode' => $sale->payment_method,
                'Balance'     => '₦' . number_format($sale->balance ?? 0, 2),
                'DueDate'     => date('d M Y', strtotime($sale->created_at . ' + 30 days')),
                'Status'      => ucfirst($sale->payment_status),
                'Class'       => ($sale->payment_status == 'paid') ? 'bg-success-light' : 'bg-danger-light',
            ];
        });

        $invoicescards = [
            ['title' => 'All Invoices', 'amount' => $sales->count(), 'class' => 'bg-primary-light', 'icon' => 'fe fe-file-text'],
            ['title' => 'Total Sales', 'amount' => '₦' . number_format($sales->sum('total'), 2), 'class' => 'bg-success-light', 'icon' => 'fe fe-database'],
            ['title' => 'Total Paid', 'amount' => '₦' . number_format($sales->sum('paid'), 2), 'class' => 'bg-info-light', 'icon' => 'fe fe-check-square'],
            ['title' => 'Total Balance', 'amount' => '₦' . number_format($sales->sum('balance'), 2), 'class' => 'bg-warning-light', 'icon' => 'fe fe-clock']
        ];

        return view('Sales.recurring-invoices', compact('invoices', 'invoicescards'));
    }

    /**
     * Signature Management.
     */
    public function signature_list()
    {
        $signatures = Signature::latest()->get();
        return view('Sales.Invoices.signature-list', compact('signatures'));
    }

    public function signature_store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'signature_image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        if ($request->hasFile('signature_image')) {
            $path = $request->file('signature_image')->store('signatures', 'public');
            Signature::create([
                'name' => $request->name,
                'image_path' => $path,
                'status' => 'active',
            ]);
            return back()->with('success', 'Signature uploaded successfully!');
        }
        return back()->with('error', 'File upload failed.');
    }

    public function signature_invoice($id = null) 
    {
        $invoice = $id ? Sale::with(['items', 'customer'])->findOrFail($id) : Sale::latest()->first();
        $signature = Signature::where('status', 'active')->first();
        return view('Sales.Invoices.signature-invoice', compact('invoice', 'signature'));
    }

    /**
     * Miscellaneous Helpers.
     */
    public function getRecentInvoices()
    {
        return response()->json(Sale::latest()->take(5)->get());
    }

    public function cashreceipt_4() { return view('Sales.Invoices.cashreceipt-4'); }
    public function signature_preview_invoice() { return view('Sales.Invoices.signature-preview-invoice'); }
    public function mail_pay_invoice() { return view('Sales.Invoices.mail-pay-invoice'); }
    public function pay_online() { return view('Sales.Invoices.pay-online'); }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string',
        ]);

        $sale = Sale::findOrFail($id);
        $status = trim((string) $request->status);

        $sale->update([
            'status' => $status,
            'payment_status' => strtolower($status),
        ]);

        return back()->with('success', 'Invoice status updated successfully.');
    }

    public function destroy($id)
    {
        Sale::findOrFail($id)->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice Deleted');
    }
}

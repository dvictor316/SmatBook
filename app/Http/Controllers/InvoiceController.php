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
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    private function onlyExistingColumns(string $table, array $payload): array
    {
        if (!Schema::hasTable($table)) {
            return $payload;
        }

        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }

    private function applyTenantScope($query, string $table)
    {
        $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (auth()->id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        }

        return $query;
    }

    /**
     * Display a listing of invoices with statistics and company branding.
     */
    public function index(Request $request)
    {
        return $this->renderInvoiceView($request);
    }

    public function invoices(Request $request)
    {
        return $this->renderInvoiceView($request);
    }

    /**
     * Centralized logic for rendering invoice views across different filters.
     */
    private function renderInvoiceView(Request $request, $statusFilter = null)
    {
        $query = Sale::with('customer')->latest();
        $this->applyTenantScope($query, 'sales');

        $effectiveStatus = $statusFilter ?: trim((string) $request->input('status', ''));
        if ($effectiveStatus !== '') {
            $query->where('payment_status', strtolower($effectiveStatus));
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('customer_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $invoices = $query->paginate(15)->withQueryString();
        $invoicescards = $this->getInvoiceStats();
        
        $latestSale = $this->applyTenantScope(Sale::query()->latest(), 'sales')->first();
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
                'amount' => $this->applyTenantScope(Sale::query(), 'sales')->sum('total'), 
                'count' => $this->applyTenantScope(Sale::query(), 'sales')->count(),
                'icon' => 'file-text',
                'class' => 'bg-primary-light'
            ],
            [
                'title' => 'Paid Invoices',
                'amount' => $this->applyTenantScope(Sale::where('payment_status', 'paid'), 'sales')->sum('total'),
                'count' => $this->applyTenantScope(Sale::where('payment_status', 'paid'), 'sales')->count(),
                'icon' => 'check-square',
                'class' => 'bg-success-light'
            ],
            [
                'title' => 'Unpaid Invoices',
                'amount' => $this->applyTenantScope(Sale::where('payment_status', 'unpaid'), 'sales')->sum('total'),
                'count' => $this->applyTenantScope(Sale::where('payment_status', 'unpaid'), 'sales')->count(),
                'icon' => 'clock',
                'class' => 'bg-warning-light'
            ],
            [
                'title' => 'Total Received',
                'amount' => $this->applyTenantScope(Sale::query(), 'sales')->sum('amount_paid'), 
                'count' => $this->applyTenantScope(Sale::where('amount_paid', '>', 0), 'sales')->count(),
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
        $customers = $this->applyTenantScope(Customer::query(), 'customers')->get();
        $products = $this->applyTenantScope(Product::query(), 'products')->get();
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
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required',
            'due_date' => 'required',
            'items' => 'required|array|min:1',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.rate' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $invoiceDate = $request->invoice_date ? Carbon::parse($request->invoice_date)->toDateString() : now()->toDateString();
            $dueDate = Carbon::parse($request->due_date)->toDateString();

            $items = collect($request->input('items', []))
                ->filter(fn ($item) => filled($item['name'] ?? null) || filled($item['product_id'] ?? null))
                ->values();

            if ($items->isEmpty()) {
                return back()->withInput()->with('error', 'Add at least one invoice item before saving.');
            }

            $customer = $this->applyTenantScope(Customer::query(), 'customers')->findOrFail((int) $request->customer_id);
            $companyId = (int) (auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
            $branchId = session('active_branch_id');
            $branchName = session('active_branch_name');

            $action = trim((string) $request->input('action', 'save'));
            $requestedStatus = strtolower(trim((string) $request->input('status', 'unpaid')));
            $isDraft = $action === 'save' || $requestedStatus === 'draft';
            $isPaid = $requestedStatus === 'paid' && !$isDraft;

            do {
                $invoiceNo = 'INV-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
            } while (Sale::withTrashed()->where('invoice_no', $invoiceNo)->exists());

            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            foreach ($items as $item) {
                $qty = (float) ($item['qty'] ?? 0);
                $rate = (float) ($item['rate'] ?? 0);
                $discount = (float) ($item['discount'] ?? 0);
                $tax = (float) ($item['tax'] ?? 0);
                $lineBase = $qty * $rate;
                $subtotal += $lineBase;
                $discountTotal += $discount;
                $taxTotal += $tax;
            }

            $totalAmount = (float) $request->total_amount;
            $paidAmount = $isPaid ? $totalAmount : 0;
            $balanceAmount = max(0, $totalAmount - $paidAmount);
            $paymentStatus = $isPaid
                ? 'paid'
                : ($requestedStatus === 'partially paid' ? 'partial' : 'unpaid');
            $orderStatus = $isDraft ? 'draft' : ($action === 'send' ? 'sent' : 'pending');

            $salePayload = [
                'invoice_no' => $invoiceNo,
                'customer_id' => $customer->id,
                'customer_name' => $customer->customer_name ?? $customer->name,
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'discount' => $discountTotal,
                'tax' => $taxTotal,
                'total' => $totalAmount,
                'paid' => $paidAmount,
                'amount_paid' => $paidAmount,
                'balance' => $balanceAmount,
                'currency' => 'NGN',
                'payment_method' => $action === 'send' ? 'send' : 'manual',
                'payment_status' => $paymentStatus,
                'payment_details' => [
                    'description' => $request->description,
                    'action' => $action,
                    'source' => 'invoice-create',
                ],
            ];

            if (Schema::hasColumn('sales', 'order_date')) {
                $salePayload['order_date'] = $invoiceDate;
            }
            if (Schema::hasColumn('sales', 'delivery_date')) {
                $salePayload['delivery_date'] = $dueDate;
            }
            if (Schema::hasColumn('sales', 'shipping_cost')) {
                $salePayload['shipping_cost'] = (float) ($request->expenses ?? 0);
            }

            if ($companyId > 0 && Schema::hasColumn('sales', 'company_id')) {
                $salePayload['company_id'] = $companyId;
            }
            if ($branchId && Schema::hasColumn('sales', 'branch_id')) {
                $salePayload['branch_id'] = $branchId;
            }
            if ($branchName && Schema::hasColumn('sales', 'branch_name')) {
                $salePayload['branch_name'] = $branchName;
            }
            if (Schema::hasColumn('sales', 'order_status')) {
                $salePayload['order_status'] = $orderStatus;
            }

            $sale = Sale::create($this->onlyExistingColumns('sales', $salePayload));

            foreach ($items as $item) {
                $qty = (float) ($item['qty'] ?? 0);
                $rate = (float) ($item['rate'] ?? 0);
                $discount = (float) ($item['discount'] ?? 0);
                $tax = (float) ($item['tax'] ?? 0);
                $lineBase = $qty * $rate;
                $lineAmount = max(0, $lineBase - $discount + $tax);
                $lineDiscountPercent = $lineBase > 0
                    ? round(min(100, ($discount / $lineBase) * 100), 2)
                    : 0;

                $saleItemPayload = [
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'] ?? null,
                    'qty' => $qty,
                    'unit_price' => $rate,
                    'discount' => $lineDiscountPercent,
                    'tax' => $tax,
                    'subtotal' => $lineBase,
                    'total_price' => $lineAmount,
                ];

                if ($companyId > 0 && Schema::hasColumn('sale_items', 'company_id')) {
                    $saleItemPayload['company_id'] = $companyId;
                }
                if ($branchId && Schema::hasColumn('sale_items', 'branch_id')) {
                    $saleItemPayload['branch_id'] = $branchId;
                }
                if ($branchName && Schema::hasColumn('sale_items', 'branch_name')) {
                    $saleItemPayload['branch_name'] = $branchName;
                }

                $sale->items()->create($this->onlyExistingColumns('sale_items', $saleItemPayload));
            }

            DB::commit();
            $message = $action === 'send'
                ? 'Invoice saved and marked ready to send.'
                : ($isDraft ? 'Invoice draft saved successfully.' : 'Invoice created successfully.');

            return redirect()->route('invoices.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }


    
    /**
     * Edit and Update Logic.
     */
    public function edit($id)
    {
        $invoice = $this->applyTenantScope(Sale::query(), 'sales')->findOrFail($id);
        $customers = $this->applyTenantScope(Customer::query(), 'customers')->get();
        return view('Sales.Invoices.edit', compact('invoice', 'customers'));
    }

    public function edit_invoice($id)
    {
        return $this->edit($id);
    }

    public function update(Request $request, $id)
    {
        $sale = $this->applyTenantScope(Sale::query(), 'sales')->findOrFail($id);
        
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
        $sale = $this->applyTenantScope(Sale::with(['customer', 'items.product']), 'sales')->findOrFail($id);
        return view('Sales.Invoices.invoice-details-admin', compact('sale'));
    }

    public function invoice_details_admin($id)
    {
        return $this->show($id);
    }

    public function invoice_details($id)
    {
        $sale = $this->applyTenantScope(Sale::with(['customer', 'items.product']), 'sales')->findOrFail($id);
        return view('Sales.Invoices.invoice-details', compact('sale'));
    }

    /**
     * Status Filter Shortcuts.
     */
    public function invoices_paid(Request $request)      { return $this->renderInvoiceView($request, 'paid'); }
    public function invoices_unpaid(Request $request)    { return $this->renderInvoiceView($request, 'unpaid'); }
    public function invoices_cancelled(Request $request) { return $this->renderInvoiceView($request, 'cancelled'); }
    public function invoices_draft(Request $request)     { return $this->renderInvoiceView($request, 'draft'); }
    public function invoices_overdue(Request $request)   { return $this->renderInvoiceView($request, 'overdue'); }
    public function invoices_recurring(Request $request) { return $this->renderInvoiceView($request, 'recurring'); }
    public function invoices_refunded(Request $request)  { return $this->renderInvoiceView($request, 'refunded'); }

    /**
     * Recurring Invoices Management.
     */
    public function recurringInvoices()
    {
        $sales = $this->applyTenantScope(Sale::with('customer')->latest(), 'sales')->get();

        $invoices = $sales->map(function ($sale) {
            return [
                'id'          => $sale->id,
                'InvoiceID'   => $sale->invoice_no,
                'Category'    => 'Sales',
                'IssuedOn'    => optional($sale->created_at)->format('d M Y') ?? date('d M Y'),
                'InvoiceTo'   => $sale->display_customer_name,
                'Image'       => 'avatar-01.jpg',
                'Email'       => $sale->customer?->email ?? 'No customer email',
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
        $invoice = $id
            ? $this->applyTenantScope(Sale::with(['items', 'customer']), 'sales')->findOrFail($id)
            : $this->applyTenantScope(Sale::query()->latest(), 'sales')->first();
        $signature = Signature::where('status', 'active')->first();
        return view('Sales.Invoices.signature-invoice', compact('invoice', 'signature'));
    }

    /**
     * Miscellaneous Helpers.
     */
    public function getRecentInvoices()
    {
        return response()->json($this->applyTenantScope(Sale::query()->latest(), 'sales')->take(5)->get());
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

        $sale = $this->applyTenantScope(Sale::query(), 'sales')->findOrFail($id);
        $status = trim((string) $request->status);

        $sale->update([
            'status' => $status,
            'payment_status' => strtolower($status),
        ]);

        return back()->with('success', 'Invoice status updated successfully.');
    }

    public function destroy($id)
    {
        $this->applyTenantScope(Sale::query(), 'sales')->findOrFail($id)->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice Deleted');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Payment;
use App\Events\NewSaleRegistered; // The Pusher event we created
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Support\LedgerService;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        // 1. Start the query with relationships
        $query = Sale::with(['customer', 'user']);

        // 2. Apply Filters
        if ($request->invoice_no) {
            $query->where('invoice_no', 'like', '%' . $request->invoice_no . '%');
        }
        if ($request->customer_name) {
            $query->whereHas('customer', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->customer_name . '%');
            });
        }
        if ($request->sale_date) {
            $query->whereDate('created_at', $request->sale_date);
        }

        // 3. Calculate Stats before pagination
        $totalRevenue = $query->sum('total');
        $totalSalesCount = $query->count();

        // 4. Paginate
        $sales = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('sales.index', compact('sales', 'totalRevenue', 'totalSalesCount'));
    }


public function customerDetails($id = null)
{
    // 1. Safety Check: If no ID, go back to list
    if (!$id) {
        return redirect()->route('customers.index')->with('error', 'Please select a customer.');
    }

    // 2. Fetch the Customer
    $customer = \App\Models\Customer::findOrFail($id);
    
    // 3. Fetch the Invoices (This was missing!)
    // Assuming your relationship is defined, or query directly:
    $invoices = \App\Models\Invoice::where('customer_id', $id)
                                   ->orderBy('created_at', 'desc')
                                   ->get();

    // 4. Prepare the Statistics for the invoices-card component
    $invoicescards = [
        'total_amount'   => $invoices->sum('total_amount'),
        'paid_amount'    => $invoices->sum('paid_amount'),
        'balance_amount' => $invoices->sum('balance'),
        'overdue_amount' => $invoices->where('status', 'overdue')->sum('balance'),
    ];

    // 5. Return View with all defined variables
    return view('Customers.customer-details', compact('customer', 'invoices', 'invoicescards'));
}


    public function showPos()
    {
        $products = Product::with('category')
            ->where('stock', '>', 0)
            ->orderBy('name', 'asc')
            ->get();
        $customers = Customer::orderBy('customer_name', 'asc')->get();
        $sales = Sale::with('customer')->latest()->take(10)->get();

        return view('pos.index', compact('products', 'customers', 'sales'));
    }

    public function showSale($id)
    {
        $sale = Sale::with(['customer', 'items.product', 'user'])->findOrFail($id);
        return view('sales.show', compact('sale'));
    }

    public function store(Request $request)
{
    $request->validate([
        'customer_id'    => 'nullable|exists:customers,id',
        'payment_method' => 'required|string',
        'total'          => 'required|numeric|min:0',
        'paid'           => 'required|numeric|min:0',
        'items'          => 'required|array|min:1',
        'items.*.id'     => 'required|exists:products,id',
        'items.*.qty'    => 'required|numeric|min:1',
    ]);

    DB::beginTransaction();

    try {
        // --- 1. GENERATE REQUIRED NUMBERS ---
        $invoiceNo = $this->generateInvoiceNo();
        $receiptNo = $this->generateReceiptNo();
        // FIXED: Generating order_number to satisfy your DB constraint
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6)); 
        
        $totalAmount = (float) $request->total;
        $amountPaid = (float) $request->paid;
        $changeAmount = $amountPaid > $totalAmount ? $amountPaid - $totalAmount : 0;
        $actualPaymentKept = $amountPaid - $changeAmount;
        $balance = $totalAmount > $actualPaymentKept ? $totalAmount - $actualPaymentKept : 0;

        $paymentStatus = ($balance <= 0) ? 'paid' : (($actualPaymentKept > 0) ? 'partial' : 'unpaid');

        $selectedCustomer = $request->customer_id ? Customer::find($request->customer_id) : null;
        $resolvedCustomerName = $selectedCustomer?->customer_name
            ?? $selectedCustomer?->name
            ?? 'Walk-in Customer';

        // --- 2. CREATE THE SALE RECORD ---
$sale = Sale::create([
    'company_id'     => auth()->user()?->company_id,
    'order_number'   => $orderNumber,
    'invoice_no'     => $invoiceNo,
    'receipt_no'     => $receiptNo,
    'customer_id'    => $request->customer_id,
    'customer_name'  => $resolvedCustomerName,
    'user_id'        => auth()->id() ?? 1,
    'terminal_id'    => 'POS1',
    'subtotal'       => 0, 
    'discount'       => 0, 
    'tax'            => 0,
    'total'          => $totalAmount,
    'paid'           => $actualPaymentKept,
    'amount_paid'    => $actualPaymentKept,
    'change_amount'  => $changeAmount,
    'balance'        => $balance,
    'currency'       => 'NGN',
    'payment_method' => $request->payment_method,
    'payment_status' => $paymentStatus,
]);

        $runningSubtotal = 0;
        $runningTax = 0;
        $runningDiscount = 0;

        // --- 3. PROCESS ITEMS ---
        foreach ($request->items as $itemData) {
            $product = Product::lockForUpdate()->find($itemData['id']);

            if ($product->stock < $itemData['qty']) {
                throw new \Exception("Insufficient stock for {$product->name}.");
            }

            $unitPrice   = (float) ($itemData['price'] ?? $product->price);
            $qty         = (float) $itemData['qty'];
            $discPercent = (float) ($itemData['discount'] ?? 0);
            $taxPercent  = (float) ($itemData['tax'] ?? 0);

            $itemSubtotal   = $unitPrice * $qty;
            $itemDiscAmount = $itemSubtotal * ($discPercent / 100);
            $afterDisc      = $itemSubtotal - $itemDiscAmount;
            $itemTaxAmount  = $afterDisc * ($taxPercent / 100);
            $itemTotal      = $afterDisc + $itemTaxAmount;

            SaleItem::create([
                'sale_id'     => $sale->id,
                'product_id'  => $product->id,
                'qty'         => $qty,
                'unit_price'  => $unitPrice,
                'discount'    => $discPercent,
                'tax'         => $taxPercent,
                'subtotal'    => $itemSubtotal,
                'total_price' => $itemTotal, 
            ]);

            $runningSubtotal += $itemSubtotal;
            $runningDiscount += $itemDiscAmount;
            $runningTax      += $itemTaxAmount;

            $product->decrement('stock', $qty);
        }

        // --- 4. UPDATE TOTALS & LOG PAYMENT ---
        $calculatedTotal = max(0, ($runningSubtotal - $runningDiscount) + $runningTax);
        $finalChange = $amountPaid > $calculatedTotal ? $amountPaid - $calculatedTotal : 0;
        $finalPaid = max(0, $amountPaid - $finalChange);
        $finalBalance = max(0, $calculatedTotal - $finalPaid);
        $finalPaymentStatus = $finalBalance <= 0 ? 'paid' : ($finalPaid > 0 ? 'partial' : 'unpaid');

        $sale->update([
            'subtotal'       => $runningSubtotal,
            'discount'       => $runningDiscount,
            'tax'            => $runningTax,
            'total'          => $calculatedTotal,
            'paid'           => $finalPaid,
            'amount_paid'    => $finalPaid,
            'change_amount'  => $finalChange,
            'balance'        => $finalBalance,
            'payment_status' => $finalPaymentStatus,
        ]);

        if ($finalPaid > 0) {
            Payment::create([
                'sale_id' => $sale->id,
                'amount'  => $finalPaid,
                'method'  => $request->payment_method,
                'note'    => 'Initial POS Payment',
            ]);
        }

        LedgerService::postSale($sale->fresh());

        // Broadcast Real-time event
        broadcast(new NewSaleRegistered($sale))->toOthers();

        DB::commit();

        if (!empty(auth()->user()?->company_id)) {
            Cache::forget('metrics_co_' . auth()->user()->company_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sale processed successfully',
            'sale_id' => $sale->id
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
    }
}

    public function showInvoice($id)
    {
        $sale = Sale::with(['items.product', 'customer'])->findOrFail($id);
        $company = Company::find(auth()->user()?->company_id) ?? Company::first() ?? new Company(['name' => 'General Store']);
        $currencySymbol = '₦'; 

        return view('Sales.Invoices.index', compact('sale', 'company', 'currencySymbol'));
    }

    private function generateInvoiceNo() {
        $latest = Sale::latest('id')->first();
        $number = $latest ? $latest->id + 1 : 1;
        return 'INV-' . strtoupper(Carbon::now()->format('ymd')) . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    private function generateReceiptNo() {
        return 'REC-' . time();
    }

    public function convertNumberToWords($number) {
        $hyphen      = '-';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $dictionary  = [
            0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
            11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen',
            20 => 'twenty', 30 => 'thirty', 40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety',
            100 => 'hundred', 1000 => 'thousand', 1000000 => 'million'
        ];

        if (!is_numeric($number)) return "";
        $number = (int) round($number);
        if ($number < 0) return $negative . $this->convertNumberToWords(abs($number));

        $string = $fraction = null;

        switch (true) {
            case $number < 21: $string = $dictionary[$number]; break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) $string .= $hyphen . $dictionary[$units];
                break;
            case $number < 1000:
                $hundreds  = (int)($number / 100);
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) $string .= $conjunction . $this->convertNumberToWords($remainder);
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convertNumberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convertNumberToWords($remainder);
                }
                break;
        }
        
        $string = str_replace(" Naira Only", "", $string);
        return ucfirst(trim($string)) . " Naira Only";
    }

    public function returnToPos()
    {
        session()->forget(['cart', 'current_customer', 'applied_discount']);
        return redirect()->route('sales.showPos')->with('success', 'Cart cleared for new transaction.');
    }


public function create()
{
    // 1. Fetch customers for the tenant
    $customers = Customer::all(); 

    // 2. Return the view with the required data
    // Update the path below to match your folder: Sales/Invoices/create-invoices
    return view('Sales.Invoices.create-invoices', compact('customers'));
}

    public function edit($id)
    {
        $sale = Sale::with('items.product')->findOrFail($id);
     $customers = Customer::orderBy('customer_name', 'asc')->get();
        $products = Product::orderBy('name', 'asc')->get();

        return view('sales.edit', compact('sale', 'customers', 'products'));
    }

    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.rate' => 'required|numeric',
        ]);

        DB::transaction(function () use ($request, $sale) {
            $sale->update([
                'customer_id' => $request->customer_id,
                'reference_no' => $request->reference_no,
                'total' => $request->final_total,
            ]);

            $sale->items()->delete();

            foreach ($request->items as $item) {
                $qty = $item['quantity'];
                $rate = $item['rate'];
                $discPercent = $item['discount'] ?? 0;
                
                $subtotal = $qty * $rate;
                $totalPrice = $subtotal - ($subtotal * ($discPercent / 100));

                $sale->items()->create([
                    'product_id'  => $item['product_id'],
                    'qty'         => $qty,
                    'unit_price'  => $rate,
                    'discount'    => $discPercent,
                    'subtotal'    => $subtotal,
                    'total_price' => $totalPrice,
                ]);
            }

            LedgerService::postSale($sale->fresh());
        });

        return redirect()->route('sales.index')->with('success', "Invoice #{$sale->invoice_no} updated.");
    }
}

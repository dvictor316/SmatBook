<?php

namespace App\Http\Controllers;

use App\Models\{Payment, Sale, Account, Transaction, Subscription, User, Company};
use App\Support\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log, File, Http, Str, Schema};
use Carbon\Carbon;
use Unicodeveloper\Paystack\Facades\Paystack;
use Flutterwave\Laravel\Facades\Flutterwave;

class PaymentController extends Controller
{
    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'payment_account_id' => 'nullable|exists:accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:191',
            'method' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|max:4096',
        ]);

        return DB::transaction(function () use ($request) {
            $sale = $request->filled('sale_id') ? Sale::find($request->sale_id) : null;
            $activeBranch = $this->getActiveBranchContext();

            $attachmentName = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
                $file->move(public_path('assets/img/payments'), $attachmentName);
            }

            $payload = [
                'sale_id' => $sale?->id,
                'branch_id' => $sale?->branch_id ?? $activeBranch['id'],
                'branch_name' => $sale?->branch_name ?? $sale?->branch_label ?? $activeBranch['name'],
                'reference' => $request->reference,
                'amount' => (float) $request->amount,
                'method' => $request->method ?: 'cash',
                'status' => $request->status ?: 'Completed',
                'note' => $request->note,
                'attachment' => $attachmentName,
                'created_by' => Auth::id(),
            ];

            if (Schema::hasColumn('payments', 'payment_account_id')) {
                $payload['payment_account_id'] = $request->payment_account_id;
            }

            $payment = Payment::create($payload);

            if ($sale) {
                $newPaid = min((float) ($sale->total ?? 0), (float) ($sale->paid ?? 0) + (float) $payment->amount);
                $newBalance = max(0, (float) ($sale->total ?? 0) - $newPaid);
                $sale->update([
                    'paid' => $newPaid,
                    'amount_paid' => $newPaid,
                    'balance' => $newBalance,
                    'payment_status' => $newBalance <= 0 ? 'paid' : 'partial',
                    'order_status' => $newBalance <= 0 ? 'completed' : ($sale->order_status ?? 'pending'),
                ]);

                LedgerService::postSalePayment($sale->fresh(), $payment, $request->reference);
            } else {
                LedgerService::postStandalonePayment($payment);
            }

            return redirect()->route('payments.index')->with('success', 'Payment recorded and ledger updated.');
        });
    }

    /**
     * 1. SHOW CHECKOUT (SaaS Setup Step 3)
     */
    public function showCheckout($id)
    {
        $subscription = Subscription::findOrFail($id);

        // Security: Ensure the user owns this subscription
        if ((int)$subscription->user_id !== (int)Auth::id()) {
            abort(403, 'Unauthorized access to this node.');
        }

        return view('Saas.checkout', compact('subscription'));
    }

    /**
     * 2. INITIALIZE PAYMENT
     */
    public function process(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'plan'   => 'nullable|string',
            'email'  => 'required|email',
            'gateway'=> 'required|string|in:paystack,flutterwave',
            'sub_id' => 'nullable|exists:subscriptions,id',
            'sale_id'=> 'nullable|exists:sales,id'
        ]);

        if ($request->gateway === 'paystack') {
            return $this->handlePaystack($request);
        }

        if ($request->gateway === 'flutterwave') {
            return $this->handleFlutterwave($request);
        }

        return redirect()->back()->with('error', 'Gateway not supported.');
    }

    protected function handlePaystack($request)
    {
        try {
            return Paystack::getAuthorizationUrl([
                "amount" => $request->amount * 100, 
                "reference" => Paystack::genTranxRef(),
                "email" => $request->email,
                "callback_url" => route('payment.callback'),
                "metadata" => [
                    "plan" => $request->plan,
                    "sub_id" => $request->sub_id,
                    "sale_id" => $request->sale_id 
                ]
            ])->redirectNow();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Paystack Error: ' . $e->getMessage());
        }
    }

    protected function handleFlutterwave($request)
    {
        try {
            $data = [
                'amount' => $request->amount,
                'email' => $request->email,
                'tx_ref' => Flutterwave::generateReference(),
                'currency' => "NGN",
                'redirect_url' => route('payment.callback'),
                'meta' => [
                    'plan' => $request->plan,
                    'sub_id' => $request->sub_id,
                    'sale_id' => $request->sale_id
                ]
            ];

            $payment = Flutterwave::initializePayment($data);
            return redirect($payment['data']['link']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Flutterwave Error: ' . $e->getMessage());
        }
    }

    /**
     * 3. UNIFIED CALLBACK HANDLER
     */
    public function handleGatewayCallback(Request $request)
    {
        $status = 'failed';
        $meta = [];
        $transactionRef = null;

        try {
            // A. Paystack Verification
            if ($request->has('reference') && !$request->has('transaction_id')) {
                $paymentDetails = Paystack::getPaymentData();
                $status = $paymentDetails['data']['status'];
                $meta = $paymentDetails['data']['metadata'] ?? [];
                $transactionRef = $request->reference;
            } 
            // B. Flutterwave Verification
            elseif ($request->has('transaction_id')) {
                $verification = Flutterwave::verifyTransaction($request->transaction_id);
                $status = $verification['data']['status'];
                $meta = $verification['data']['meta'] ?? [];
                $transactionRef = $request->transaction_id;
            }
            if ($status === 'success' || $status === 'successful') {
                return $this->executeSuccessfulPayment($meta, $transactionRef);
            }

        } catch (\Exception $e) {
            Log::error("Payment Verification Error: " . $e->getMessage());
            return redirect()->route('home')->with('error', 'Verification Error: ' . $e->getMessage());
        }

        return redirect()->route('home')->with('error', 'Payment failed or was cancelled.');
    }

  /**
     * 4. DATABASE EXECUTION & NODE ACTIVATION
     */
    protected function executeSuccessfulPayment($meta, $transactionRef)
    {
        return DB::transaction(function () use ($meta, $transactionRef) {
            
            // 1. SaaS Activation Logic (Subscription/Node Uplink)
            if (isset($meta['sub_id'])) {
                $subscription = Subscription::find($meta['sub_id']);
                
                if ($subscription) {
                    // Update Subscription Status
                    $subscription->update([
                        'status' => 'active',
                        'payment_status' => 'paid',
                        'paid_at' => now(),
                        'transaction_reference' => $transactionRef
                    ]);

                    // --- CRITICAL: ACTIVATE THE COMPANY NODE ---
                    // This is what stops the persistence middleware from redirecting back to checkout
                    $company = Company::where('user_id', $subscription->user_id)->first();
                    
                    if ($company) {
                        $company->update([
                            'status' => 'active',
                            'onboarding_step' => 4, // Final checkpoint: Dashboard Access Granted
                            'plan' => $subscription->plan_name ?? $company->plan, // Sync plan name
                        ]);
                    }
                }
            }

            // 2. Retail/POS Sale Logic (One-time invoice payment)
            if (isset($meta['sale_id'])) {
                $sale = Sale::find($meta['sale_id']);
                
                if ($sale) {
                    $amountDue = (float) ($sale->balance ?? 0);
                    if ($amountDue <= 0) {
                        $amountDue = max(0, (float) ($sale->total ?? 0) - (float) ($sale->paid ?? 0));
                    }
                    if ($amountDue <= 0) {
                        $amountDue = (float) ($sale->total ?? 0);
                    }

                    $newPaid = min((float) ($sale->total ?? 0), (float) ($sale->paid ?? 0) + $amountDue);
                    $newBalance = max(0, (float) ($sale->total ?? 0) - $newPaid);
                    $newStatus = $newBalance <= 0 ? 'paid' : 'partial';

                    $sale->update([
                        'paid' => $newPaid,
                        'amount_paid' => $newPaid,
                        'balance' => $newBalance,
                        'payment_status' => $newStatus,
                        'order_status' => $newBalance <= 0 ? 'completed' : ($sale->order_status ?? 'pending'),
                    ]);

                    $payment = Payment::create([
                        'payment_id' => 'ONLINE-' . strtoupper(Str::random(6)),
                        'sale_id'    => $sale->id,
                        'branch_id'  => $sale->branch_id,
                        'branch_name'=> $sale->branch_name ?? $sale->branch_label,
                        'amount'     => $amountDue,
                        'method'     => 'Online Gateway',
                        'status'     => 'Completed',
                        'created_by' => Auth::id() ?? $sale->user_id,
                    ]);

                    LedgerService::postSalePayment($sale->fresh(), $payment, $transactionRef);
                }
            }

            // 3. SECURE REDIRECT
            return redirect()->route('user.dashboard')
                ->with('success', 'Payment confirmed. Your workspace is now active.');
        });
    }

    /**
     * 5. FINANCE & REPORTING
     */
    public function index()
    {
        $data = [
            'payments'      => Payment::with(['sale', 'creator'])->latest()->paginate(10),
            'sales'         => Sale::select('id', 'invoice_no')->get(),
            'assetAccounts' => Account::where('type', 'Asset')->where('is_active', 1)->get(),
        ];
        return view('Finance.payments', $data);
    }

    public function payment_report(Request $request)
    {
        $query = Payment::with(['sale', 'creator']);

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', Carbon::parse($request->from_date));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', Carbon::parse($request->to_date));
        }

        $totalAmount = (clone $query)->sum('amount');
        $payments = $query->latest()->paginate(25);

        return view('Reports.Reports.payment-report', compact('payments', 'totalAmount'))
               ->with(['from_date' => $request->from_date, 'to_date' => $request->to_date]);
    }

    public function destroy(Payment $payment)
    {
        try {
            DB::transaction(function () use ($payment) {
                $sale = $payment->sale;
                Transaction::where('related_id', $payment->id)
                           ->where('related_type', Payment::class)
                           ->delete();
                
                if ($payment->attachment) {
                    File::delete(public_path('assets/img/payments/' . $payment->attachment));
                }
                $payment->delete();

                if ($sale) {
                    $paid = (float) $sale->payments()->sum('amount');
                    $balance = max(0, (float) ($sale->total ?? 0) - $paid);
                    $sale->update([
                        'paid' => $paid,
                        'amount_paid' => $paid,
                        'balance' => $balance,
                        'payment_status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                        'order_status' => $balance <= 0 ? 'completed' : 'pending',
                    ]);
                }
            });
            return redirect()->route('payments.index')->with('success', 'Transaction purged.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Payment $payment)
    {
        return redirect()->route('payments.receipt', $payment->id)
            ->with('info', 'Payment receipt opened.');
    }

    public function create()
    {
        return redirect()->route('payments.index')
            ->with('info', 'Use the payment form on this page to record a new payment.');
    }

    public function edit(Payment $payment)
    {
        return redirect()->route('payments.index')
            ->with('info', 'Direct payment editing is not enabled yet. Use delete and recreate if needed.');
    }

    public function update(Request $request, Payment $payment)
    {
        return redirect()->route('payments.index')->with('info', 'Update is not enabled on this page yet.');
    }

    public function receipt(Payment $payment)
    {
        $payment->loadMissing(['sale', 'creator']);
        return view('Finance.payment-receipt', compact('payment'));
    }

    public function getBySale($saleId)
    {
        $payments = Payment::query()
            ->where('sale_id', $saleId)
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $payments]);
    }

    public function statistics()
    {
        $query = Payment::query();
        $total = (float) $query->sum('amount');
        $count = (int) $query->count();
        $today = (float) Payment::query()->whereDate('created_at', now()->toDateString())->sum('amount');

        return response()->json([
            'total_amount' => $total,
            'count' => $count,
            'today_amount' => $today,
        ]);
    }

    public function download($filename)
    {
        $path = public_path('assets/img/payments/' . $filename);
        if (!File::exists($path)) {
            return back()->with('error', 'Attachment not found.');
        }
        return response()->download($path);
    }
}

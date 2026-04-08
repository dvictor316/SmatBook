<?php

namespace App\Http\Controllers;

use App\Models\{Payment, Sale, Account, Bank, Transaction, Subscription, User, Company, Customer, FinanceApproval};
use App\Support\LedgerService;
use App\Traits\HasUniqueReceiptNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log, File, Http, Schema, Storage};
use Illuminate\Support\Str;
use Carbon\Carbon;
use Unicodeveloper\Paystack\Facades\Paystack;
use Flutterwave\Laravel\Facades\Flutterwave;

class PaymentController extends Controller
{
    use HasUniqueReceiptNumber;

    private function applyTenantScope($query, string $table)
    {
        $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (Auth::id() ?? 0);
        $scoped = false;

        if ($table === 'payments' && $companyId > 0 && !Schema::hasColumn($table, 'company_id')) {
            $query->where(function ($sub) use ($companyId) {
                if (Schema::hasTable('sales') && Schema::hasColumn('payments', 'sale_id') && Schema::hasColumn('sales', 'company_id')) {
                    $sub->whereHas('sale', function ($saleQuery) use ($companyId) {
                        $saleQuery->where('company_id', $companyId);
                    });
                }

                if (Schema::hasTable('users') && Schema::hasColumn('payments', 'created_by') && Schema::hasColumn('users', 'company_id')) {
                    $sub->orWhereHas('creator', function ($creatorQuery) use ($companyId) {
                        $creatorQuery->where('company_id', $companyId);
                    });
                }
            });
            $scoped = true;
        }

        if (!$scoped && $companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
            $scoped = true;
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
            $scoped = true;
        } elseif ($userId > 0 && Schema::hasColumn($table, 'created_by')) {
            $query->where("{$table}.created_by", $userId);
            $scoped = true;
        }

        return $query;
    }

    private function getActiveBranchContext(): array
    {
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }

    private function applyBranchScope($query, string $table = 'payments')
    {
        $activeBranch = $this->getActiveBranchContext();

        $branchId = (string) ($activeBranch['id'] ?? '');
        $branchName = (string) ($activeBranch['name'] ?? '');

        if ($branchId !== '' || $branchName !== '') {
            $query->where(function ($sub) use ($table, $branchId, $branchName) {
                if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                    $sub->where("{$table}.branch_id", $branchId);
                }
                if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                    $sub->orWhere("{$table}.branch_name", $branchName);
                }
            });
        }

        return $query;
    }

    private function findScopedPayment(int|string $paymentId): ?Payment
    {
        $baseQuery = Payment::query();
        $this->applyTenantScope($baseQuery, 'payments');

        $branchQuery = clone $baseQuery;
        $this->applyBranchScope($branchQuery, 'payments');
        $payment = $branchQuery->find($paymentId);

        return $payment ?: $baseQuery->find($paymentId);
    }

    public function store(Request $request)
    {
        $requiresSaleId = false;
        if (Schema::hasTable('payments')) {
            try {
                $columnInfo = DB::select("SHOW COLUMNS FROM payments WHERE Field = 'sale_id'");
                if (!empty($columnInfo) && isset($columnInfo[0]->Null)) {
                    $requiresSaleId = strtolower((string) $columnInfo[0]->Null) === 'no';
                }
            } catch (\Throwable $e) {
                $requiresSaleId = false;
            }
        }

        $request->validate([
            'sale_id' => ($requiresSaleId ? 'required' : 'nullable') . '|exists:sales,id',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_account_id' => 'nullable|exists:accounts,id',
            'bank_id' => Schema::hasTable('banks') ? 'nullable|exists:banks,id' : 'nullable',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:191',
            'method' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'require_approval' => 'nullable|boolean',
        ]);

        if (!$request->filled('sale_id') && !$request->filled('customer_id')) {
            return back()->withInput()->with('error', 'Select a customer or link a sale reference for this payment.');
        }
        if ($requiresSaleId && !$request->filled('sale_id')) {
            return back()->withInput()->with('error', 'Select the sale reference for this payment.');
        }

        try {
            return DB::transaction(function () use ($request) {
                $sale = $request->filled('sale_id')
                    ? $this->applyTenantScope(Sale::query(), 'sales')->find($request->sale_id)
                    : null;
                $customer = (!$sale && $request->filled('customer_id'))
                    ? $this->applyTenantScope(Customer::query(), 'customers')->find($request->customer_id)
                    : null;
                $activeBranch = $this->getActiveBranchContext();

                $attachmentName = null;
                if ($request->hasFile('attachment')) {
                    $file = $request->file('attachment');
                    $attachmentName = time() . '_' . Str::uuid() . '.' . strtolower((string) $file->getClientOriginalExtension());
                    Storage::disk('public')->putFileAs('payments', $file, $attachmentName);
                }

                $resolvedAccountId = $request->payment_account_id;
                if (!$resolvedAccountId && $request->filled('bank_id') && Schema::hasTable('accounts')) {
                    $bank = Bank::find((int) $request->bank_id);
                    if ($bank) {
                        $accountName = $bank->name ?? $bank->bank_name ?? 'Bank Account';
                        $createPayload = [
                            'type' => 'Asset',
                            'is_active' => 1,
                            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
                            'user_id' => Auth::id(),
                        ];
                        if (Schema::hasColumn('accounts', 'code')) {
                            $createPayload['code'] = 'BANK-' . strtoupper(Str::random(5));
                        }
                        if (Schema::hasColumn('accounts', 'balance')) {
                            $createPayload['balance'] = 0;
                        }
                        $account = Account::firstOrCreate(['name' => $accountName], $createPayload);
                        $resolvedAccountId = $account->id;
                    }
                }
                if (!$resolvedAccountId && Schema::hasTable('accounts')) {
                    $fallbackAccount = Account::query()->where('type', 'Asset')->where('is_active', 1)->first();
                    if (!$fallbackAccount) {
                        $payload = [
                            'name' => 'Main Bank Account',
                            'type' => 'Asset',
                            'is_active' => 1,
                            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
                            'user_id' => Auth::id(),
                        ];
                        if (Schema::hasColumn('accounts', 'code')) {
                            $payload['code'] = 'BANK-' . strtoupper(Str::random(5));
                        }
                        if (Schema::hasColumn('accounts', 'balance')) {
                            $payload['balance'] = 0;
                        }
                        $fallbackAccount = Account::create($payload);
                    }
                    $resolvedAccountId = $fallbackAccount?->id;
                }

                $requiresApproval = $request->boolean('require_approval');

                $payload = [
                    'sale_id' => $sale?->id,
                    'customer_id' => $sale?->customer_id ?? $customer?->id,
                    'branch_id' => $sale?->branch_id ?? $activeBranch['id'],
                    'branch_name' => $sale?->branch_name ?? $sale?->branch_label ?? $activeBranch['name'],
                    'reference' => $request->reference,
                    'receipt_no' => $this->generatePaymentReceiptNo(),
                    'amount' => (float) $request->amount,
                    'method' => $request->method ?: 'cash',
                    'status' => $requiresApproval ? 'Pending Approval' : ($request->status ?: 'Pending'),
                    'note' => $request->note,
                    'attachment' => $attachmentName,
                    'created_by' => Auth::id(),
                ];

                if (Schema::hasColumn('payments', 'payment_account_id')) {
                    $payload['payment_account_id'] = $resolvedAccountId;
                }
                if (Schema::hasColumn('payments', 'company_id')) {
                    $payload['company_id'] = Auth::user()?->company_id ?? session('current_tenant_id');
                }
                if (Schema::hasColumn('payments', 'user_id')) {
                    $payload['user_id'] = Auth::id();
                }

                $payment = Payment::create($payload);

                if (empty($payment->payment_id)) {
                    $payment->update([
                        'payment_id' => 'PAY-' . str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT),
                    ]);
                    $payment->refresh();
                }

                if ($requiresApproval) {
                    FinanceApproval::create([
                        'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
                        'branch_id' => $payment->branch_id,
                        'branch_name' => $payment->branch_name,
                        'requested_by' => Auth::id(),
                        'approval_type' => 'payment',
                        'approvable_type' => Payment::class,
                        'approvable_id' => $payment->id,
                        'reference_no' => $payment->reference ?: ($payment->payment_id ?? ('PAY-' . $payment->id)),
                        'title' => 'Payment approval for ' . ($customer?->customer_name ?? $customer?->name ?? $sale?->customer?->customer_name ?? $sale?->customer?->name ?? ($payment->payment_id ?? ('Payment #' . $payment->id))),
                        'amount' => $payment->amount,
                        'status' => 'pending',
                        'submitted_at' => now(),
                        'snapshot' => [
                            'defer_posting' => true,
                            'sale_id' => $sale?->id,
                            'customer_id' => $customer?->id ?? $sale?->customer_id,
                        ],
                    ]);

                    return redirect()->route('payments.index')->with('success', 'Payment saved and sent for approval.');
                }

                if ($sale) {
                    $newPaid = min((float) ($sale->total ?? 0), (float) ($sale->paid ?? 0) + (float) $payment->amount);
                    $newBalance = max(0, (float) ($sale->total ?? 0) - $newPaid);
                    $saleUpdate = [
                        'paid' => $newPaid,
                        'amount_paid' => $newPaid,
                        'balance' => $newBalance,
                        'payment_status' => $newBalance <= 0 ? 'paid' : 'partial',
                    ];

                    if (\Illuminate\Support\Facades\Schema::hasColumn('sales', 'order_status')) {
                        $saleUpdate['order_status'] = $newBalance <= 0 ? 'completed' : ($sale->order_status ?? 'pending');
                    }

                    $sale->update($saleUpdate);

                    $payment->update([
                        'status' => $newBalance <= 0 ? 'Completed' : 'Pending',
                        'note' => $request->note ?: ($newBalance <= 0 ? 'Payment completed' : 'Deposit received'),
                    ]);

                    LedgerService::postSalePayment($sale->fresh(), $payment, $request->reference);
                } else {
                    if ($customer && Schema::hasColumn('customers', 'balance')) {
                        $currentBalance = (float) ($customer->balance ?? 0);
                        $customer->update([
                            'balance' => max(0, $currentBalance - (float) $payment->amount),
                        ]);
                    }

                    if ($customer) {
                        LedgerService::postCustomerPayment($payment);
                    } else {
                        LedgerService::postStandalonePayment($payment);
                    }
                }

                return redirect()->route('payments.index')->with('success', 'Payment recorded and ledger updated.');
            });
        } catch (\Throwable $e) {
            Log::error('Payment store failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return back()->withInput()->with('error', 'Payment could not be saved. ' . $e->getMessage());
        }
    }

    /**
     * 1. SHOW CHECKOUT (SaaS Setup Step 3)
     */
    public function showCheckout($id)
    {
        $subscription = Subscription::findOrFail($id);

        if ($subscription->payment_status === 'paid' || strtolower((string) $subscription->status) === 'active') {
            return redirect()->route('home')
                ->with('info', 'Your subscription is already active.');
        }

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
                        'receipt_no' => $this->generatePaymentReceiptNo(),
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
    public function index(Request $request)
    {
        $paymentsBase = Payment::with(['sale', 'creator'])->latest();
        $this->applyTenantScope($paymentsBase, 'payments');
        $paymentsQuery = clone $paymentsBase;
        $this->applyBranchScope($paymentsQuery, 'payments');
        if (!(clone $paymentsQuery)->exists()) {
            $paymentsQuery = $paymentsBase;
        }

        $status = trim((string) $request->string('status'));
        $search = trim((string) $request->string('q'));
        $month = trim((string) $request->string('month'));
        $fromDate = trim((string) $request->string('from_date'));
        $toDate = trim((string) $request->string('to_date'));

        if ($status !== '') {
            $paymentsQuery->where('status', $status);
        }
        if ($search !== '') {
            $paymentsQuery->where(function ($query) use ($search) {
                $query->where('payment_id', 'like', '%' . $search . '%')
                    ->orWhere('reference', 'like', '%' . $search . '%')
                    ->orWhere('method', 'like', '%' . $search . '%');

                if (Schema::hasTable('customers')) {
                    $query->orWhereHas('customer', function ($sub) use ($search) {
                        if (Schema::hasColumn('customers', 'customer_name')) {
                            $sub->where('customer_name', 'like', '%' . $search . '%');
                        }
                        if (Schema::hasColumn('customers', 'name')) {
                            $method = Schema::hasColumn('customers', 'customer_name') ? 'orWhere' : 'where';
                            $sub->{$method}('name', 'like', '%' . $search . '%');
                        }
                    });
                }
            });
        }
        if ($month !== '') {
            $paymentsQuery->whereBetween('created_at', [
                now()->parse($month . '-01')->startOfMonth()->toDateString(),
                now()->parse($month . '-01')->endOfMonth()->toDateString(),
            ]);
        } else {
            if ($fromDate !== '') {
                $paymentsQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate !== '') {
                $paymentsQuery->whereDate('created_at', '<=', $toDate);
            }
        }

        $salesBase = Sale::select('id', 'invoice_no', 'customer_id', 'balance', 'total', 'paid', 'amount_paid');
        $this->applyTenantScope($salesBase, 'sales');
        $salesQuery = clone $salesBase;
        $this->applyBranchScope($salesQuery, 'sales');
        if (!(clone $salesQuery)->exists()) {
            $salesQuery = $salesBase;
        }

        $selectedCustomer = null;
        $selectedSaleId = $request->filled('sale_id') ? (int) $request->query('sale_id') : null;
        $openPayment = $request->boolean('open_payment');
        $selectedSaleBalance = null;
        $outstandingBalance = null;

        if ($request->filled('customer_id') && Schema::hasTable('customers')) {
            $customerId = (int) $request->query('customer_id');
            $selectedCustomer = $this->applyTenantScope(Customer::query(), 'customers')->find($customerId);

            if ($selectedCustomer) {
                $salesQuery->where('customer_id', $selectedCustomer->id);
                if (Schema::hasColumn('sales', 'balance')) {
                    $salesQuery->where('balance', '>', 0);
                }
            }
        }

        $orderColumn = 'id';
        if (Schema::hasColumn('customers', 'customer_name')) {
            $orderColumn = 'customer_name';
        } elseif (Schema::hasColumn('customers', 'name')) {
            $orderColumn = 'name';
        }
        $customersQuery = Customer::query()->orderBy($orderColumn, 'asc');
        $this->applyTenantScope($customersQuery, 'customers');
        $this->applyBranchScope($customersQuery, 'customers');

        $assetAccountsQuery = Account::where('type', 'Asset')->where('is_active', 1);
        $this->applyTenantScope($assetAccountsQuery, 'accounts');

        $bankAccounts = Schema::hasTable('banks')
            ? Bank::query()->orderBy('name')->get()
            : collect();

        if (!empty($selectedSaleId)) {
            $selectedSaleBalance = (float) (Sale::query()->whereKey($selectedSaleId)->value('balance') ?? 0);
        }

        if ($selectedCustomer && Schema::hasColumn('sales', 'balance')) {
            $outstandingBalance = (float) Sale::query()
                ->where('customer_id', $selectedCustomer->id)
                ->where('balance', '>', 0)
                ->sum('balance');
        }

        $pendingApprovalIds = FinanceApproval::query()
            ->where('approvable_type', Payment::class)
            ->where('status', 'pending')
            ->pluck('approvable_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $data = [
            'payments'      => $paymentsQuery->paginate(10)->appends($request->query()),
            'sales'         => $salesQuery->get(),
            'assetAccounts' => $assetAccountsQuery->get(),
            'bankAccounts'  => $bankAccounts,
            'selectedCustomer' => $selectedCustomer,
            'selectedSaleId' => $selectedSaleId,
            'selectedSaleBalance' => $selectedSaleBalance,
            'outstandingBalance' => $outstandingBalance,
            'openPayment' => $openPayment,
            'customers' => $customersQuery->get(),
            'pendingApprovalIds' => $pendingApprovalIds,
            'status' => $status,
            'search' => $search,
            'month' => $month,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ];
        return view('Finance.payments', $data);
    }

    public function payment_report(Request $request)
    {
        $query = Payment::with(['sale', 'creator']);
        $this->applyTenantScope($query, 'payments');
        $this->applyBranchScope($query, 'payments');

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
        $payment = $this->findScopedPayment($payment->id);
        abort_if(!$payment, 404);

        try {
            DB::transaction(function () use ($payment) {
                $sale = $payment->sale;
                Transaction::where('related_id', $payment->id)
                           ->where('related_type', Payment::class)
                           ->delete();
                
                if ($payment->attachment) {
                    $filepath = $this->resolveAttachmentPath($payment->attachment);
                    if ($filepath && file_exists($filepath)) {
                        @unlink($filepath);
                    }
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
        $payment = $this->findScopedPayment($payment->id);
        abort_if(!$payment, 404);
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
        $payment = $this->findScopedPayment($payment->id);
        abort_if(!$payment, 404);
        return redirect()->route('payments.index')
            ->with('info', 'Direct payment editing is not enabled yet. Use delete and recreate if needed.');
    }

    public function update(Request $request, Payment $payment)
    {
        $payment = $this->findScopedPayment($payment->id);
        abort_if(!$payment, 404);
        return redirect()->route('payments.index')->with('info', 'Update is not enabled on this page yet.');
    }

    public function receipt(Payment $payment)
    {
        $payment = $this->findScopedPayment($payment->id);
        abort_if(!$payment, 404);
        $payment->loadMissing(['sale.items', 'sale.customer', 'customer', 'creator', 'account']);
        return view('Finance.payment-receipt', compact('payment'));
    }

    public function getBySale($saleId)
    {
        $paymentsQuery = Payment::query()->where('sale_id', $saleId)->orderByDesc('id');
        $this->applyTenantScope($paymentsQuery, 'payments');
        $this->applyBranchScope($paymentsQuery, 'payments');
        $payments = $paymentsQuery->get();

        return response()->json(['data' => $payments]);
    }

    public function statistics()
    {
        $query = $this->applyTenantScope(Payment::query(), 'payments');
        $this->applyBranchScope($query, 'payments');
        $total = (float) $query->sum('amount');
        $count = (int) $query->count();
        $todayQuery = $this->applyTenantScope(Payment::query()->whereDate('created_at', now()->toDateString()), 'payments');
        $this->applyBranchScope($todayQuery, 'payments');
        $today = (float) $todayQuery->sum('amount');

        return response()->json([
            'total_amount' => $total,
            'count' => $count,
            'today_amount' => $today,
        ]);
    }

    public function download($filename)
    {
        $payment = $this->applyTenantScope(Payment::query(), 'payments')
            ->where('attachment', $filename)
            ->first();

        if (!$payment) {
            return back()->with('error', 'Attachment not found.');
        }

        $path = $this->resolveAttachmentPath($payment->attachment);
        if (!$path || !file_exists($path)) {
            return back()->with('error', 'Attachment not found.');
        }

        return response()->download($path);
    }

    private function resolveAttachmentPath(?string $filename): ?string
    {
        if (!$filename) {
            return null;
        }

        $publicPath = public_path('assets/img/payments/' . $filename);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        $storagePath = storage_path('app/public/payments/' . $filename);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        return null;
    }
}

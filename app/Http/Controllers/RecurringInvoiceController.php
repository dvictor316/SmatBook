<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RecurringInvoiceLog;
use App\Models\RecurringInvoiceTemplate;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\RecurringInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class RecurringInvoiceController extends Controller
{
    public function __construct(private readonly RecurringInvoiceService $service) {}

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $companyId  = $this->companyId();
        $branchId   = session('active_branch_id');
        $branchName = session('active_branch_name');
        $allBranches = session('active_branch_scope') === 'all' || strtolower((string) $branchId) === 'all';

        $base = RecurringInvoiceTemplate::with('customer')
            ->when($companyId > 0, fn($q) => $q->where('company_id', $companyId))
            ->when(!$allBranches && $companyId > 0 && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when(!$allBranches && $companyId > 0 && $branchName && !$branchId, fn($q) => $q->where('branch_name', $branchName));

        // Filters
        if ($status = $request->query('status')) {
            $base->where('status', $status);
        }
        if ($freq = $request->query('frequency')) {
            $base->where('frequency', $freq);
        }
        if ($search = $request->query('q')) {
            $base->where(function ($q) use ($search) {
                $q->where('template_name', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $templates = (clone $base)->latest()->paginate(20)->appends($request->query());

        // Summary stats
        $stats = [
            'active'    => (clone $base)->where('status', 'active')->count(),
            'paused'    => (clone $base)->where('status', 'paused')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'overdue'   => (clone $base)->where('status', 'active')->whereDate('next_run_on', '<', today())->count(),
            'forecast'  => (clone $base)->where('status', 'active')->sum('total'),
            'failed'    => RecurringInvoiceLog::whereIn(
                                'template_id',
                                RecurringInvoiceTemplate::where('company_id', $companyId)->pluck('id')
                            )->where('status', 'failed')->whereMonth('created_at', now()->month)->count(),
            'generated_this_month' => RecurringInvoiceLog::whereIn(
                                'template_id',
                                RecurringInvoiceTemplate::where('company_id', $companyId)->pluck('id')
                            )->where('status', 'success')->whereMonth('created_at', now()->month)->count(),
        ];

        $customers = Customer::when($companyId > 0, fn($q) => $q->where('company_id', $companyId))
            ->orderBy('customer_name')
            ->get(['id', 'customer_name', 'email']);

        return view('Sales.recurring-invoices.index', compact(
            'templates', 'stats', 'customers'
        ));
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $companyId = $this->companyId();
        $customers = Customer::where('company_id', $companyId)
            ->orderBy('customer_name')
            ->get(['id', 'customer_name', 'email', 'currency']);

        // Optional: pre-fill from existing sale
        $prefillSale = null;
        if ($saleId = $request->query('from_sale')) {
            $prefillSale = Sale::with('items', 'customer')
                ->where('company_id', $companyId)
                ->find($saleId);
        }

        return view('Sales.recurring-invoices.create', compact('customers', 'prefillSale'));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_name'        => 'required|string|max:191',
            'customer_id'          => 'nullable|integer|exists:customers,id',
            'currency'             => 'nullable|string|max:10',
            'terms'                => 'nullable|string|max:100',
            'due_days'             => 'nullable|integer|min:0|max:365',
            'notes'                => 'nullable|string|max:1000',
            'internal_memo'        => 'nullable|string|max:1000',
            'payment_instructions' => 'nullable|string|max:1000',
            'payment_link_enabled' => 'nullable|boolean',
            'auto_payment_enabled' => 'nullable|boolean',
            'email_subject'        => 'nullable|string|max:255',
            'send_email'           => 'nullable|boolean',
            'timezone'             => 'nullable|timezone',

            // Recurrence
            'frequency'    => 'required|in:daily,weekly,biweekly,monthly,quarterly,semi_annual,annual,custom',
            'interval_value' => 'nullable|integer|min:1|max:365',
            'interval_unit'  => 'nullable|in:days,weeks,months,years',
            'date_rule'    => 'nullable|in:specific_day,first_of_month,last_of_month,business_day',
            'specific_day' => 'nullable|integer|min:1|max:28',
            'skip_weekends' => 'nullable|boolean',

            // Automation
            'automation_mode' => 'required|in:draft,auto_send,reminder_only,manual',

            // Schedule
            'starts_on'       => 'required|date',
            'end_type'        => 'required|in:never,date,count',
            'ends_on'         => 'nullable|date|after:starts_on',
            'max_occurrences' => 'nullable|integer|min:1|max:9999',

            // Notification timing
            'reminder_before_days' => 'nullable|array',
            'reminder_before_days.*' => 'integer|min:0|max:90',
            'reminder_after_days'  => 'nullable|array',
            'reminder_after_days.*' => 'integer|min:0|max:180',

            // Items
            'items'                 => 'required|array|min:1',
            'items.*.product_name'  => 'required|string|max:255',
            'items.*.qty'           => 'required|numeric|min:0.01',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.discount'      => 'nullable|numeric|min:0',
            'items.*.tax'           => 'nullable|numeric|min:0',
            'items.*.product_id'    => 'nullable|integer',
        ]);

        $companyId  = $this->companyId();
        $branchId   = session('active_branch_id');
        $branchName = session('active_branch_name');
        $allBranches = session('active_branch_scope') === 'all' || strtolower((string) $branchId) === 'all';
        if ($allBranches) {
            $branchId = null;
            $branchName = null;
        }

        // Resolve customer name
        $customerName = null;
        if (!empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $customerName = $customer?->customer_name ?? $customer?->name;
        }

        // Calculate totals from items
        $items    = $validated['items'];
        $subtotal = 0;
        $taxTotal = 0;
        $discTotal = 0;

        $items = array_map(function ($item) use (&$subtotal, &$taxTotal, &$discTotal) {
            $qty      = (float) $item['qty'];
            $price    = (float) $item['unit_price'];
            $tax      = (float) ($item['tax'] ?? 0);
            $disc     = (float) ($item['discount'] ?? 0);
            $lineSubt = round($qty * $price, 2);
            $lineTotal = round($lineSubt + $tax - $disc, 2);

            $subtotal += $lineSubt;
            $taxTotal += $tax;
            $discTotal += $disc;

            return array_merge($item, [
                'subtotal'    => $lineSubt,
                'total_price' => $lineTotal,
            ]);
        }, $items);

        $total = round($subtotal + $taxTotal - $discTotal, 2);

        // Calculate first next_run_on
        $startsOn    = Carbon::parse($validated['starts_on']);
        $nextRunOn   = $startsOn->toDateString();

        $template = RecurringInvoiceTemplate::create([
            'company_id'           => $companyId,
            'branch_id'            => $branchId,
            'branch_name'          => $branchName,
            'created_by'           => Auth::id(),
            'updated_by'           => Auth::id(),
            'customer_id'          => $validated['customer_id'] ?? null,
            'customer_name'        => $customerName,
            'template_name'        => $validated['template_name'],
            'notes'                => $validated['notes'] ?? null,
            'internal_memo'        => $validated['internal_memo'] ?? null,
            'payment_instructions' => $validated['payment_instructions'] ?? null,
            'payment_link_enabled' => (bool) ($validated['payment_link_enabled'] ?? true),
            'auto_payment_enabled' => (bool) ($validated['auto_payment_enabled'] ?? false),
            'email_subject'        => $validated['email_subject'] ?? null,
            'send_email'           => (bool) ($validated['send_email'] ?? true),
            'currency'             => $validated['currency'] ?? 'NGN',
            'terms'                => $validated['terms'] ?? null,
            'due_days'             => (int) ($validated['due_days'] ?? 30),
            'timezone'             => $validated['timezone'] ?? config('app.timezone', 'Africa/Lagos'),
            'frequency'            => $validated['frequency'],
            'interval_value'       => (int) ($validated['interval_value'] ?? 1),
            'interval_unit'        => $validated['interval_unit'] ?? 'months',
            'date_rule'            => $validated['date_rule'] ?? 'specific_day',
            'specific_day'         => $validated['specific_day'] ?? null,
            'skip_weekends'        => (bool) ($validated['skip_weekends'] ?? false),
            'automation_mode'      => $validated['automation_mode'],
            'starts_on'            => $validated['starts_on'],
            'next_run_on'          => $nextRunOn,
            'end_type'             => $validated['end_type'],
            'ends_on'              => $validated['ends_on'] ?? null,
            'max_occurrences'      => $validated['max_occurrences'] ?? null,
            'occurrences_count'    => 0,
            'status'               => 'active',
            'items'                => $items,
            'subtotal'             => round($subtotal, 2),
            'tax_amount'           => round($taxTotal, 2),
            'discount'             => round($discTotal, 2),
            'total'                => $total,
            'reminder_before_days' => $validated['reminder_before_days'] ?? [],
            'reminder_after_days'  => $validated['reminder_after_days'] ?? [],
        ]);

        return redirect()
            ->route('sales.recurring-invoices.index')
            ->with('success', "Recurring template \"{$template->template_name}\" created. Next run: {$template->next_run_on->format('d M Y')}.");
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function show(RecurringInvoiceTemplate $recurringInvoice)
    {
        $this->authorizeTemplate($recurringInvoice);
        $recurringInvoice->load('customer', 'logs.sale', 'creator');

        return view('Sales.recurring-invoices.show', [
            'template' => $recurringInvoice,
            'logs'     => $recurringInvoice->logs()->paginate(20),
        ]);
    }

    // ─── Edit ─────────────────────────────────────────────────────────────────

    public function edit(RecurringInvoiceTemplate $recurringInvoice)
    {
        $this->authorizeTemplate($recurringInvoice);

        $customers = Customer::where('company_id', $this->companyId())
            ->orderBy('customer_name')
            ->get(['id', 'customer_name', 'email', 'currency']);

        return view('Sales.recurring-invoices.edit', [
            'template'  => $recurringInvoice,
            'customers' => $customers,
        ]);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, RecurringInvoiceTemplate $recurringInvoice)
    {
        $this->authorizeTemplate($recurringInvoice);

        $validated = $request->validate([
            'template_name'        => 'required|string|max:191',
            'customer_id'          => 'nullable|integer|exists:customers,id',
            'currency'             => 'nullable|string|max:10',
            'terms'                => 'nullable|string|max:100',
            'due_days'             => 'nullable|integer|min:0|max:365',
            'notes'                => 'nullable|string|max:1000',
            'internal_memo'        => 'nullable|string|max:1000',
            'payment_instructions' => 'nullable|string|max:1000',
            'payment_link_enabled' => 'nullable|boolean',
            'auto_payment_enabled' => 'nullable|boolean',
            'email_subject'        => 'nullable|string|max:255',
            'send_email'           => 'nullable|boolean',
            'timezone'             => 'nullable|timezone',
            'frequency'            => 'required|in:daily,weekly,biweekly,monthly,quarterly,semi_annual,annual,custom',
            'interval_value'       => 'nullable|integer|min:1|max:365',
            'interval_unit'        => 'nullable|in:days,weeks,months,years',
            'date_rule'            => 'nullable|in:specific_day,first_of_month,last_of_month,business_day',
            'specific_day'         => 'nullable|integer|min:1|max:28',
            'skip_weekends'        => 'nullable|boolean',
            'automation_mode'      => 'required|in:draft,auto_send,reminder_only,manual',
            'starts_on'            => 'required|date',
            'end_type'             => 'required|in:never,date,count',
            'ends_on'              => 'nullable|date|after:starts_on',
            'max_occurrences'      => 'nullable|integer|min:1|max:9999',
            'reminder_before_days' => 'nullable|array',
            'reminder_before_days.*' => 'integer|min:0|max:90',
            'reminder_after_days'    => 'nullable|array',
            'reminder_after_days.*'  => 'integer|min:0|max:180',
            'items'                  => 'required|array|min:1',
            'items.*.product_name'   => 'required|string|max:255',
            'items.*.qty'            => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.discount'       => 'nullable|numeric|min:0',
            'items.*.tax'            => 'nullable|numeric|min:0',
            'items.*.product_id'     => 'nullable|integer',
        ]);

        $customerName = null;
        if (!empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $customerName = $customer?->customer_name ?? $customer?->name;
        }

        $items    = $validated['items'];
        $subtotal = 0;
        $taxTotal = 0;
        $discTotal = 0;

        $items = array_map(function ($item) use (&$subtotal, &$taxTotal, &$discTotal) {
            $qty   = (float) $item['qty'];
            $price = (float) $item['unit_price'];
            $tax   = (float) ($item['tax'] ?? 0);
            $disc  = (float) ($item['discount'] ?? 0);
            $lineSubt = round($qty * $price, 2);
            $subtotal  += $lineSubt;
            $taxTotal  += $tax;
            $discTotal += $disc;
            return array_merge($item, ['subtotal' => $lineSubt, 'total_price' => round($lineSubt + $tax - $disc, 2)]);
        }, $items);

        $total = round($subtotal + $taxTotal - $discTotal, 2);

        $recurringInvoice->update([
            'customer_id'          => $validated['customer_id'] ?? null,
            'customer_name'        => $customerName,
            'template_name'        => $validated['template_name'],
            'notes'                => $validated['notes'] ?? null,
            'internal_memo'        => $validated['internal_memo'] ?? null,
            'payment_instructions' => $validated['payment_instructions'] ?? null,
            'payment_link_enabled' => (bool) ($validated['payment_link_enabled'] ?? true),
            'auto_payment_enabled' => (bool) ($validated['auto_payment_enabled'] ?? false),
            'email_subject'        => $validated['email_subject'] ?? null,
            'send_email'           => (bool) ($validated['send_email'] ?? true),
            'currency'             => $validated['currency'] ?? 'NGN',
            'terms'                => $validated['terms'] ?? null,
            'due_days'             => (int) ($validated['due_days'] ?? 30),
            'timezone'             => $validated['timezone'] ?? config('app.timezone', 'Africa/Lagos'),
            'frequency'            => $validated['frequency'],
            'interval_value'       => (int) ($validated['interval_value'] ?? 1),
            'interval_unit'        => $validated['interval_unit'] ?? 'months',
            'date_rule'            => $validated['date_rule'] ?? 'specific_day',
            'specific_day'         => $validated['specific_day'] ?? null,
            'skip_weekends'        => (bool) ($validated['skip_weekends'] ?? false),
            'automation_mode'      => $validated['automation_mode'],
            'starts_on'            => $validated['starts_on'],
            'end_type'             => $validated['end_type'],
            'ends_on'              => $validated['ends_on'] ?? null,
            'max_occurrences'      => $validated['max_occurrences'] ?? null,
            'items'                => $items,
            'subtotal'             => round($subtotal, 2),
            'tax_amount'           => round($taxTotal, 2),
            'discount'             => round($discTotal, 2),
            'total'                => $total,
            'reminder_before_days' => $validated['reminder_before_days'] ?? [],
            'reminder_after_days'  => $validated['reminder_after_days'] ?? [],
            'updated_by'           => Auth::id(),
        ]);

        return redirect()
            ->route('sales.recurring-invoices.show', $recurringInvoice)
            ->with('success', 'Recurring invoice template updated.');
    }

    // ─── Actions ──────────────────────────────────────────────────────────────

    /** Manually run (generate now) */
    public function run(RecurringInvoiceTemplate $recurringInvoice)
    {
        $this->authorizeTemplate($recurringInvoice);

        try {
            $sale = $this->service->runManual($recurringInvoice);
            return redirect()
                ->route('sales.recurring-invoices.show', $recurringInvoice)
                ->with('success', "Invoice {$sale->invoice_no} generated successfully.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** Pause an active template */
    public function pause(RecurringInvoiceTemplate $recurringInvoice)
    {
        $this->authorizeTemplate($recurringInvoice);
        $recurringInvoice->update(['status' => 'paused', 'updated_by' => Auth::id()]);
        return back()->with('success', 'Recurring template paused.');
    }

    /** Resume a paused template */
    public function resume(RecurringInvoiceTemplate $recurringInvoice)
    {
        $this->authorizeTemplate($recurringInvoice);
        $recurringInvoice->update(['status' => 'active', 'updated_by' => Auth::id()]);
        return back()->with('success', 'Recurring template resumed.');
    }

    /** Cancel – soft terminal status */
    public function cancel(RecurringInvoiceTemplate $recurringInvoice)
    {
        $this->authorizeTemplate($recurringInvoice);
        $recurringInvoice->update([
            'status'     => 'cancelled',
            'updated_by' => Auth::id(),
        ]);
        return redirect()
            ->route('sales.recurring-invoices.index')
            ->with('success', 'Recurring template cancelled.');
    }

    public function archive(RecurringInvoiceTemplate $recurringInvoice)
    {
        $this->authorizeTemplate($recurringInvoice);
        $recurringInvoice->update([
            'status' => 'archived',
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('sales.recurring-invoices.index')
            ->with('success', 'Recurring template archived.');
    }

    /** Clone a template */
    public function cloneTemplate(RecurringInvoiceTemplate $recurringInvoice)
    {
        $this->authorizeTemplate($recurringInvoice);

        $clone = $recurringInvoice->replicate();
        $clone->template_name     = $recurringInvoice->template_name . ' (Copy)';
        $clone->status            = 'active';
        $clone->occurrences_count = 0;
        $clone->last_run_on       = null;
        $clone->next_run_on       = today()->toDateString();
        $clone->starts_on         = today();
        $clone->created_by        = Auth::id();
        $clone->updated_by        = Auth::id();
        $clone->failure_count     = 0;
        $clone->last_failure_at   = null;
        $clone->last_failure_message = null;
        $clone->save();

        return redirect()
            ->route('sales.recurring-invoices.edit', $clone)
            ->with('success', 'Template cloned. Please review and save.');
    }

    /** Convert a Sale to a recurring template */
    public function fromSale(Request $request, Sale $sale)
    {
        $companyId = $this->companyId();
        if ((int) $sale->company_id !== $companyId) {
            abort(403);
        }

        $items = $sale->items->map(fn($i) => [
            'product_id'   => $i->product_id,
            'product_name' => $i->product_name,
            'qty'          => $i->qty,
            'unit_price'   => $i->unit_price,
            'discount'     => $i->discount ?? 0,
            'tax'          => $i->tax ?? 0,
            'subtotal'     => $i->subtotal,
            'total_price'  => $i->total_price,
        ])->toArray();

        return redirect()->route('sales.recurring-invoices.create', ['from_sale' => $sale->id])
                         ->with('prefill', [
                             'customer_id' => $sale->customer_id,
                             'currency'    => $sale->currency,
                             'items'       => $items,
                             'total'       => $sale->total,
                         ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function isSuperAdmin(): bool
    {
        $user = Auth::user();
        return $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
    }

    private function companyId(): int
    {
        return (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
    }

    private function authorizeTemplate(RecurringInvoiceTemplate $template): void
    {
        if ($this->isSuperAdmin()) {
            return; // super admin can access any company's template
        }
        if ((int) $template->company_id !== $this->companyId()) {
            abort(403);
        }
    }
}

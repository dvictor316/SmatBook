<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\FinanceApproval;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RecurringTransaction;
use App\Support\BranchInventoryService;
use App\Support\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecurringTransactionController extends Controller
{
    public function __construct(private readonly BranchInventoryService $branchInventory)
    {
    }

    private function applyTenantScope($query, string $table)
    {
        $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (Auth::id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        }

        return $query;
    }

    private function getActiveBranchContext(): array
    {
        $branchId = session('active_branch_id') ? (string) session('active_branch_id') : null;
        $branchName = session('active_branch_name') ? (string) session('active_branch_name') : null;

        if (!$branchId && !$branchName && Schema::hasTable('settings')) {
            $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
            if ($companyId > 0) {
                $key = 'branches_json_company_' . $companyId;
                $raw = (string) (DB::table('settings')->where('key', $key)->value('value') ?? '');
                $branches = json_decode($raw, true) ?: [];
                $first = collect($branches)->first();
                if ($first) {
                    $branchId = $branchId ?: ($first['id'] ?? null);
                    $branchName = $branchName ?: ($first['name'] ?? null);
                }
            }
        }

        return [
            'id' => $branchId,
            'name' => $branchName,
        ];
    }

    private function applyBranchScope($query, string $table)
    {
        $activeBranch = $this->getActiveBranchContext();
        $branchId = trim((string) ($activeBranch['id'] ?? ''));
        $branchName = trim((string) ($activeBranch['name'] ?? ''));

        if ($branchId === '' && $branchName === '') {
            return $query;
        }

        return $query->where(function ($sub) use ($table, $branchId, $branchName) {
            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $sub->where("{$table}.branch_id", $branchId);
            }
            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $sub->orWhere("{$table}.branch_name", $branchName);
            }
        });
    }

    public function index()
    {
        $activeBranch = $this->getActiveBranchContext();

        $templatesQuery = RecurringTransaction::query()->latest();
        $this->applyTenantScope($templatesQuery, 'recurring_transactions');
        $this->applyBranchScope($templatesQuery, 'recurring_transactions');
        $templates = $templatesQuery->paginate(15);

        $expenseSourceQuery = Expense::query()->latest();
        $this->applyTenantScope($expenseSourceQuery, 'expenses');
        $this->applyBranchScope($expenseSourceQuery, 'expenses');
        $expenseSources = $expenseSourceQuery->limit(50)->get();

        $purchaseSourceQuery = Purchase::with(['supplier', 'vendor'])->latest();
        $this->applyTenantScope($purchaseSourceQuery, 'purchases');
        $this->applyBranchScope($purchaseSourceQuery, 'purchases');
        $purchaseSources = $purchaseSourceQuery->limit(50)->get();

        return view('Finance.recurring-transactions', compact(
            'templates',
            'expenseSources',
            'purchaseSources',
            'activeBranch'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_type' => 'required|in:expense,purchase',
            'source_id' => 'required|integer|min:1',
            'name' => 'required|string|max:191',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly,yearly',
            'interval_value' => 'nullable|integer|min:1|max:12',
            'starts_on' => 'nullable|date',
            'next_run_on' => 'required|date',
            'ends_on' => 'nullable|date|after_or_equal:next_run_on',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:active,paused',
            'auto_post' => 'nullable|boolean',
            'approval_required' => 'nullable|boolean',
        ]);

        $record = $this->resolveSourceRecord($validated['source_type'], (int) $validated['source_id']);

        if (!$record) {
            return back()->withInput()->with('error', 'The selected source transaction was not found for this tenant/branch.');
        }

        $activeBranch = $this->getActiveBranchContext();

        RecurringTransaction::create([
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => $activeBranch['id'] ?? ($record->branch_id ?? null),
            'branch_name' => $activeBranch['name'] ?? ($record->branch_name ?? null),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'source_type' => $validated['source_type'],
            'source_id' => (int) $validated['source_id'],
            'name' => $validated['name'],
            'frequency' => $validated['frequency'],
            'interval_value' => (int) ($validated['interval_value'] ?? 1),
            'starts_on' => $validated['starts_on'] ?? $validated['next_run_on'],
            'next_run_on' => $validated['next_run_on'],
            'ends_on' => $validated['ends_on'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'auto_post' => (bool) ($validated['auto_post'] ?? false),
            'approval_required' => (bool) ($validated['approval_required'] ?? false),
            'notes' => $validated['notes'] ?? null,
            'payload' => [
                'source_reference' => $this->resolveSourceReference($validated['source_type'], $record),
                'source_name' => $this->resolveSourceName($validated['source_type'], $record),
            ],
        ]);

        return redirect()->route('finance.recurring.index')->with('success', 'Recurring transaction template created successfully.');
    }

    public function createFromExpense(Expense $expense)
    {
        $expense = $this->scopeExpenseQuery()->findOrFail($expense->id);

        RecurringTransaction::create([
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => $expense->branch_id,
            'branch_name' => $expense->branch_name,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'source_type' => 'expense',
            'source_id' => $expense->id,
            'name' => 'Recurring Expense - ' . ($expense->company_name ?: $expense->expense_id),
            'frequency' => 'monthly',
            'interval_value' => 1,
            'starts_on' => now()->toDateString(),
            'next_run_on' => now()->addMonth()->toDateString(),
            'status' => 'active',
            'auto_post' => false,
            'approval_required' => true,
            'payload' => [
                'source_reference' => $expense->expense_id,
                'source_name' => $expense->company_name,
            ],
        ]);

        return redirect()->route('finance.recurring.index')->with('success', 'Expense recurring template created successfully.');
    }

    public function createFromPurchase(Purchase $purchase)
    {
        $purchase = $this->scopePurchaseQuery()->findOrFail($purchase->id);

        RecurringTransaction::create([
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => $purchase->branch_id,
            'branch_name' => $purchase->branch_name,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'source_type' => 'purchase',
            'source_id' => $purchase->id,
            'name' => 'Recurring Purchase - ' . ($purchase->purchase_no ?: ('PUR-' . $purchase->id)),
            'frequency' => 'monthly',
            'interval_value' => 1,
            'starts_on' => now()->toDateString(),
            'next_run_on' => now()->addMonth()->toDateString(),
            'status' => 'active',
            'auto_post' => false,
            'approval_required' => true,
            'payload' => [
                'source_reference' => $purchase->purchase_no,
                'source_name' => $purchase->supplier?->name ?? $purchase->vendor?->name ?? 'Supplier Purchase',
            ],
        ]);

        return redirect()->route('finance.recurring.index')->with('success', 'Purchase recurring template created successfully.');
    }

    public function run(RecurringTransaction $recurringTransaction)
    {
        $template = $this->scopeRecurringQuery()->findOrFail($recurringTransaction->id);

        if (($template->status ?? 'active') !== 'active') {
            return back()->with('error', 'Paused templates cannot be run until reactivated.');
        }

        return DB::transaction(function () use ($template) {
            $createdLabel = null;

            if ($template->source_type === 'expense') {
                $expense = $this->cloneExpense($template);
                $createdLabel = $expense->expense_id ?? ('Expense #' . $expense->id);
            } else {
                $purchase = $this->clonePurchase($template);
                $createdLabel = $purchase->purchase_no ?? ('Purchase #' . $purchase->id);
            }

            $nextRunOn = $this->calculateNextRunDate($template);
            $template->update([
                'last_run_on' => now(),
                'next_run_on' => $nextRunOn?->toDateString(),
                'updated_by' => Auth::id(),
            ]);

            return redirect()->route('finance.recurring.index')
                ->with('success', 'Recurring template ran successfully and created ' . $createdLabel . '.');
        });
    }

    public function toggleStatus(RecurringTransaction $recurringTransaction)
    {
        $template = $this->scopeRecurringQuery()->findOrFail($recurringTransaction->id);
        $template->update([
            'status' => $template->status === 'active' ? 'paused' : 'active',
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('finance.recurring.index')->with('success', 'Recurring template status updated.');
    }

    private function cloneExpense(RecurringTransaction $template): Expense
    {
        $source = $this->scopeExpenseQuery()->findOrFail($template->source_id);
        $nextId = (int) Expense::max('id') + 1;
        $expenseId = 'EXP-' . date('Y') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
        $isPaidClone = (bool) $template->auto_post && strtolower((string) ($source->status ?? '')) === 'paid';

        $payload = [
            'expense_id' => $expenseId,
            'company_name' => $source->company_name,
            'reference' => trim((string) ($source->reference ?: '')) !== '' ? ($source->reference . ' [Recurring]') : 'Recurring Expense',
            'email' => $source->email,
            'amount' => $source->amount,
            'payment_mode' => $source->payment_mode,
            'payment_status' => $isPaidClone ? 'paid' : 'pending',
            'category_id' => $source->category_id ?? null,
            'category' => $source->category,
            'notes' => trim((string) (($source->notes ?? '') . "\n" . ($template->notes ?? ''))),
            'status' => $isPaidClone ? 'Paid' : 'Pending',
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => $template->branch_id ?: $source->branch_id,
            'branch_name' => $template->branch_name ?: $source->branch_name,
            'created_by' => Auth::id(),
            'image' => null,
        ];

        $expense = Expense::create($payload);

        if ($template->approval_required) {
            $this->createApproval(
                'expense',
                $expense,
                (float) $expense->amount,
                $expense->expense_id,
                'Recurring expense approval for ' . ($expense->company_name ?: $expense->expense_id),
                ['defer_posting' => $isPaidClone]
            );
        } elseif ($isPaidClone) {
            LedgerService::postExpense($expense->fresh());
        }

        return $expense;
    }

    private function clonePurchase(RecurringTransaction $template): Purchase
    {
        $source = $this->scopePurchaseQuery()->with('items')->findOrFail($template->source_id);
        $purchaseNo = 'PUR-' . now()->format('ymdHis') . '-' . strtoupper(substr(md5((string) microtime(true)), 0, 4));

        $payload = [
            'purchase_no' => $purchaseNo,
            'supplier_id' => $source->supplier_id ?? null,
            'vendor_id' => $source->vendor_id ?? null,
            'bank_id' => $source->bank_id ?? null,
            'tax_id' => $source->tax_id ?? null,
            'total_amount' => $source->total_amount ?? 0,
            'tax_amount' => $source->tax_amount ?? 0,
            'paid_amount' => 0,
            'status' => $template->approval_required ? 'pending approval' : ($source->status ?: 'received'),
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'user_id' => Auth::id(),
            'branch_id' => $template->branch_id ?: $source->branch_id,
            'branch_name' => $template->branch_name ?: $source->branch_name,
        ];

        $purchase = Purchase::create(array_filter($payload, fn ($value) => $value !== null || $value === 0));

        foreach ($source->items as $item) {
            $itemPayload = [
                'purchase_id' => $purchase->id,
                'product_id' => $item->product_id,
                'qty' => (float) ($item->qty ?? $item->quantity ?? 0),
                'unit_price' => (float) ($item->unit_price ?? $item->rate ?? 0),
            ];

            if (Schema::hasColumn('purchase_items', 'quantity')) {
                $itemPayload['quantity'] = (float) ($item->quantity ?? $item->qty ?? 0);
            }
            if (Schema::hasColumn('purchase_items', 'rate')) {
                $itemPayload['rate'] = (float) ($item->rate ?? $item->unit_price ?? 0);
            }
            if (Schema::hasColumn('purchase_items', 'discount')) {
                $itemPayload['discount'] = (float) ($item->discount ?? 0);
            }
            if (Schema::hasColumn('purchase_items', 'tax_id')) {
                $itemPayload['tax_id'] = $item->tax_id ?? null;
            }
            if (Schema::hasColumn('purchase_items', 'amount')) {
                $qty = (float) ($itemPayload['qty'] ?? 0);
                $price = (float) ($itemPayload['unit_price'] ?? 0);
                $itemPayload['amount'] = round($qty * $price, 2);
            }
            if (Schema::hasColumn('purchase_items', 'company_id')) {
                $itemPayload['company_id'] = $payload['company_id'];
            }
            if (Schema::hasColumn('purchase_items', 'branch_id')) {
                $itemPayload['branch_id'] = $payload['branch_id'];
            }
            if (Schema::hasColumn('purchase_items', 'branch_name')) {
                $itemPayload['branch_name'] = $payload['branch_name'];
            }

            PurchaseItem::create($itemPayload);

            $product = $item->product()->lockForUpdate()->first();
            if ($product) {
                $quantity = (float) ($itemPayload['qty'] ?? $itemPayload['quantity'] ?? 0);
                $product->increment('stock', $quantity);
                if (Schema::hasColumn('products', 'stock_quantity')) {
                    $product->increment('stock_quantity', $quantity);
                }
                $this->branchInventory->adjustBranchStock(
                    $product,
                    $quantity,
                    ['id' => $payload['branch_id'] ?? null, 'name' => $payload['branch_name'] ?? null],
                    (int) ($payload['company_id'] ?? 0)
                );
            }
        }

        if ($template->approval_required) {
            $this->createApproval(
                'purchase',
                $purchase,
                (float) ($purchase->total_amount ?? 0),
                $purchase->purchase_no,
                'Recurring purchase approval for ' . ($purchase->purchase_no ?: ('Purchase #' . $purchase->id)),
                ['defer_posting' => true]
            );
        } else {
            LedgerService::postPurchase($purchase->fresh());
        }

        return $purchase;
    }

    private function createApproval(string $type, $approvable, float $amount, ?string $reference, string $title, array $snapshot = []): void
    {
        FinanceApproval::create([
            'company_id' => Auth::user()?->company_id ?? session('current_tenant_id'),
            'branch_id' => $approvable->branch_id ?? session('active_branch_id'),
            'branch_name' => $approvable->branch_name ?? session('active_branch_name'),
            'requested_by' => Auth::id(),
            'approval_type' => $type,
            'approvable_type' => $approvable::class,
            'approvable_id' => $approvable->id,
            'reference_no' => $reference,
            'title' => $title,
            'amount' => $amount,
            'status' => 'pending',
            'submitted_at' => now(),
            'snapshot' => $snapshot,
        ]);
    }

    private function calculateNextRunDate(RecurringTransaction $template): ?Carbon
    {
        $base = $template->next_run_on
            ? Carbon::parse($template->next_run_on)
            : now();
        $interval = max(1, (int) ($template->interval_value ?? 1));

        $next = match ($template->frequency) {
            'daily' => $base->copy()->addDays($interval),
            'weekly' => $base->copy()->addWeeks($interval),
            'quarterly' => $base->copy()->addMonths(3 * $interval),
            'yearly' => $base->copy()->addYears($interval),
            default => $base->copy()->addMonths($interval),
        };

        if ($template->ends_on && $next->gt(Carbon::parse($template->ends_on))) {
            return null;
        }

        return $next;
    }

    private function resolveSourceRecord(string $sourceType, int $sourceId): Expense|Purchase|null
    {
        if ($sourceType === 'expense') {
            return $this->scopeExpenseQuery()->find($sourceId);
        }

        return $this->scopePurchaseQuery()->find($sourceId);
    }

    private function resolveSourceReference(string $sourceType, Expense|Purchase $record): ?string
    {
        return $sourceType === 'expense'
            ? ($record->expense_id ?? null)
            : ($record->purchase_no ?? null);
    }

    private function resolveSourceName(string $sourceType, Expense|Purchase $record): string
    {
        if ($sourceType === 'expense') {
            return (string) ($record->company_name ?: ($record->expense_id ?? 'Expense'));
        }

        return (string) ($record->supplier?->name ?? $record->vendor?->name ?? ($record->purchase_no ?? 'Purchase'));
    }

    private function scopeExpenseQuery()
    {
        $query = Expense::query();
        $this->applyTenantScope($query, 'expenses');
        $this->applyBranchScope($query, 'expenses');

        return $query;
    }

    private function scopePurchaseQuery()
    {
        $query = Purchase::query();
        $this->applyTenantScope($query, 'purchases');
        $this->applyBranchScope($query, 'purchases');

        return $query;
    }

    private function scopeRecurringQuery()
    {
        $query = RecurringTransaction::query();
        $this->applyTenantScope($query, 'recurring_transactions');
        $this->applyBranchScope($query, 'recurring_transactions');

        return $query;
    }
}

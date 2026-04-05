<?php

namespace App\Http\Controllers;

use App\Models\Expense;      // Correct
use App\Models\Transaction;  // Correct
use App\Models\Account;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ExpenseController extends Controller
{
    private function applyTenantScope($query, string $table)
    {
        $companyId = (int) (Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (Auth::id() ?? 0);

        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'created_by')) {
            $query->where("{$table}.created_by", $userId);
        }

        return $query;
    }

    private function applyBranchScope($query, string $table = 'expenses')
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

        if ($branchId) {
            session(['active_branch_id' => $branchId]);
        }
        if ($branchName) {
            session(['active_branch_name' => $branchName]);
        }

        return [
            'id' => $branchId,
            'name' => $branchName,
        ];
    }

    private function getSessionBranchContext(): array
    {
        return [
            'id' => session('active_branch_id') ? (string) session('active_branch_id') : null,
            'name' => session('active_branch_name') ? (string) session('active_branch_name') : null,
        ];
    }

    public function index()
    {
        $this->syncBanksToAssetAccounts();

        $expensesQuery = Expense::with('creator')->latest();
        $this->applyTenantScope($expensesQuery, 'expenses');
        $this->applyBranchScope($expensesQuery, 'expenses');
        $expenses = $expensesQuery->paginate(15);
        $expenseAccounts = Schema::hasTable('accounts')
            ? $this->applyTenantScope(Account::where('type', 'Expense')->orderBy('name'), 'accounts')->get()
            : collect();
        $assetAccounts = Schema::hasTable('accounts')
            ? $this->applyTenantScope(Account::where('type', 'Asset')->orderBy('name'), 'accounts')->get()
            : collect();
        $categories = Schema::hasTable('categories')
            ? $this->applyTenantScope(Category::orderBy('name'), 'categories')->get(['id', 'name'])
            : collect();

        $partyOptions = collect();
        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'name')) {
            $partyOptions = $partyOptions->merge($this->applyTenantScope(Vendor::query()->orderBy('name'), 'vendors')->pluck('name'));
        }
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'customer_name')) {
            $partyOptions = $partyOptions->merge($this->applyTenantScope(Customer::query()->orderBy('customer_name'), 'customers')->pluck('customer_name'));
        }
        if (Schema::hasColumn('expenses', 'company_name')) {
            $partyOptions = $partyOptions->merge(
                $this->applyTenantScope(Expense::query()->whereNotNull('company_name')->orderBy('company_name'), 'expenses')->pluck('company_name')
            );
        }
        $partyOptions = $partyOptions->filter()->unique()->values();

        return view('Finance.expenses', compact('expenses', 'expenseAccounts', 'assetAccounts', 'partyOptions', 'categories'));
    }

    public function store(Request $request)
    {
        $sessionBranch = $this->getSessionBranchContext();
        if (empty($sessionBranch['id']) && empty($sessionBranch['name'])) {
            return back()->withInput()->with('error', 'Please select a branch before posting an expense.');
        }

        $request->validate([
            'company_name' => 'required|string|max:191',
            'email' => 'nullable|email|max:191',
            'reference' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|string',
            'payment_account_id' => 'required|exists:accounts,id',
            'status' => 'required|in:Paid,Pending,Overdue',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        try {
            return DB::transaction(function () use ($request, $sessionBranch) {
                [$expenseAccount, $categoryId] = $this->resolveExpenseAccountFromSelector((string) $request->account_id);
                $paymentAccount = Account::findOrFail($request->payment_account_id);
                $nextId = (int) Expense::max('id') + 1;
                $expenseId = 'EXP-' . date('Y') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
                $payload = [
                    'expense_id'     => $expenseId,
                    'company_name'   => $request->company_name,
                    'reference'      => $request->reference,
                    'email'          => $request->email,
                    'amount'         => $request->amount,
                    'payment_mode'   => $paymentAccount->name,
                    'payment_status' => strtolower($request->status) === 'paid' ? 'paid' : 'pending',
                    'category'       => $expenseAccount->name,
                    'category_id'    => Schema::hasColumn('expenses', 'category_id') ? $categoryId : null,
                    'notes'          => $request->notes,
                    'status'         => $request->status,
                    'created_by'     => Auth::id(),
                    'image'          => $this->handleFileUpload($request),
                ];

                if (Schema::hasColumn('expenses', 'company_id')) {
                    $payload['company_id'] = Auth::user()?->company_id ?? session('current_tenant_id');
                }
                if (Schema::hasColumn('expenses', 'user_id')) {
                    $payload['user_id'] = Auth::id();
                }
                if (Schema::hasColumn('expenses', 'branch_id')) {
                    $payload['branch_id'] = $sessionBranch['id'];
                }
                if (Schema::hasColumn('expenses', 'branch_name')) {
                    $payload['branch_name'] = $sessionBranch['name'];
                }

                $expense = Expense::create($payload);

                if ($request->status === 'Paid') {
                    Transaction::where('related_id', $expense->id)
                        ->where('related_type', Expense::class)
                        ->delete();

                    \App\Support\LedgerService::postExpense($expense->fresh());
                }

                return redirect()->route('expenses.index')->with('success', 'Expense saved successfully.');
            });
        } catch (\Throwable $e) {
            Log::error('Expense store failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'branch_id' => $sessionBranch['id'] ?? null,
                'branch_name' => $sessionBranch['name'] ?? null,
            ]);
            return back()->withInput()->with('error', 'Expense could not be saved. ' . $e->getMessage());
        }
    }

    private function handleFileUpload($request)
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        $image = $request->file('image');
        $imageName = time() . '_' . uniqid('', true) . '.' . $image->getClientOriginalExtension();

        try {
            if (Storage::disk('public')->putFileAs('expenses', $image, $imageName)) {
                return $imageName;
            }
        } catch (\Throwable $e) {
            Log::error('Expense receipt upload failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
        }

        // Don't block expense creation if the attachment fails.
        return null;
    }

    public function update(Request $request, $id)
    {
        $expenseQuery = $this->applyTenantScope(Expense::query(), 'expenses');
        $this->applyBranchScope($expenseQuery, 'expenses');
        $expense = $expenseQuery->find($id);
        if (!$expense) {
            $expense = $this->applyTenantScope(Expense::query(), 'expenses')->find($id);
        }
        if (!$expense) {
            return redirect()
                ->route('expenses.index')
                ->with('error', 'Expense not found for the active branch.');
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:191',
            'email' => 'nullable|email|max:191',
            'reference' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01|max:999999999999.99',
            'account_id' => 'nullable|string',
            'payment_account_id' => 'nullable|exists:accounts,id',
            'status' => 'required|string|in:Pending,Paid,Overdue',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated, $expense) {
                $categoryId = Schema::hasColumn('expenses', 'category_id') ? ($expense->category_id ?? null) : null;
                $categoryName = $expense->category ?: null;
                if (!empty($validated['account_id'])) {
                    [$expenseAccount, $categoryId] = $this->resolveExpenseAccountFromSelector((string) $validated['account_id']);
                $categoryName = $expenseAccount->name;
            }
            if (!$categoryName) {
                $fallbackAccount = $this->applyTenantScope(Account::where('type', 'Expense'), 'accounts')->first();
                $categoryName = $fallbackAccount?->name ?: 'General Expense';
            }
            $paymentAccount = null;
            if (!empty($validated['payment_account_id'])) {
                $paymentAccount = Account::findOrFail((int) $validated['payment_account_id']);
            } elseif ($validated['status'] === 'Paid' && empty($expense->payment_mode)) {
                Log::warning('Expense update blocked: paid status without payment source', [
                    'expense_id' => $expense->id,
                    'status' => $validated['status'] ?? null,
                    'payment_mode' => $expense->payment_mode ?? null,
                    'payment_account_id' => $validated['payment_account_id'] ?? null,
                    'user_id' => Auth::id(),
                ]);
                return back()->withInput()->with('error', 'Select a Paid From (Credit Account) to mark this expense as Paid.');
            }

            if ($request->hasFile('image')) {
                $this->deleteExpenseAttachment($expense->image);
                $validated['image'] = $this->handleFileUpload($request);
            }

            $updatePayload = [
                'company_name'   => $validated['company_name'],
                'reference'      => $validated['reference'] ?? null,
                'email'          => $validated['email'] ?? null,
                'amount'         => $validated['amount'],
                'payment_status' => strtolower($validated['status']) === 'paid' ? 'paid' : 'pending',
                'category'       => $categoryName,
                'category_id'    => Schema::hasColumn('expenses', 'category_id') ? $categoryId : null,
                'notes'          => $validated['notes'] ?? null,
                'status'         => $validated['status'],
                'image'          => $validated['image'] ?? $expense->image,
            ];

            if ($paymentAccount) {
                $updatePayload['payment_mode'] = $paymentAccount->name;
            }

            $expense->update($updatePayload);

            if (Schema::hasColumn('expenses', 'branch_id') && empty($expense->branch_id)) {
                $expense->branch_id = session('active_branch_id');
            }
            if (Schema::hasColumn('expenses', 'branch_name') && empty($expense->branch_name)) {
                $expense->branch_name = session('active_branch_name');
            }
            if (Schema::hasColumn('expenses', 'company_id') && empty($expense->company_id)) {
                $expense->company_id = Auth::user()?->company_id ?? session('current_tenant_id');
            }
            if (Schema::hasColumn('expenses', 'user_id') && empty($expense->user_id)) {
                $expense->user_id = Auth::id();
            }
            $expense->save();

            // Rebuild ledger entries for this expense to keep accounting accurate.
            Transaction::where('related_id', $expense->id)
                ->where('related_type', Expense::class)
                ->where('transaction_type', 'Expense')
                ->delete();

            if ($validated['status'] === 'Paid') {
                \App\Support\LedgerService::postExpense($expense->fresh());
            }

            Log::info('Expense updated', [
                'expense_id' => $expense->id,
                'status' => $expense->status,
                'payment_mode' => $expense->payment_mode,
                'user_id' => Auth::id(),
            ]);

                return redirect()->route('expenses.index')->with('success', 'Expense updated successfully!');
            });
        } catch (\Throwable $e) {
            Log::error('Expense update failed', [
                'expense_id' => $expense->id ?? null,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return back()->withInput()->with('error', 'Expense update failed. ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $expenseQuery = $this->applyTenantScope(Expense::query(), 'expenses');
        $this->applyBranchScope($expenseQuery, 'expenses');
        $expense = $expenseQuery->find($id);
        if (!$expense) {
            return redirect()
                ->route('expenses.index')
                ->with('error', 'Expense not found for the active branch.');
        }

        Transaction::where('related_id', $expense->id)
            ->where('related_type', Expense::class)
            ->where('transaction_type', 'Expense')
            ->delete();

        // Delete image if exists
        $this->deleteExpenseAttachment($expense->image);

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully!');
    }

    public function markPaid(Request $request, $id)
    {
        $expenseQuery = $this->applyTenantScope(Expense::query(), 'expenses');
        $this->applyBranchScope($expenseQuery, 'expenses');
        $expense = $expenseQuery->find($id);
        if (!$expense) {
            return redirect()
                ->route('expenses.index')
                ->with('error', 'Expense not found for the active branch.');
        }

        try {
            $paymentAccount = null;
            if ($request->filled('payment_account_id')) {
                $paymentAccount = Account::findOrFail((int) $request->input('payment_account_id'));
                $expense->payment_mode = $paymentAccount->name;
            }

            if (empty($expense->payment_mode)) {
                return redirect()
                    ->route('expenses.index')
                    ->with('error', 'Select a Paid From (Credit Account) before marking this expense as Paid.');
            }

            $expense->status = 'Paid';
            $expense->payment_status = 'paid';
            $expense->save();

            Transaction::where('related_id', $expense->id)
                ->where('related_type', Expense::class)
                ->where('transaction_type', 'Expense')
                ->delete();

            \App\Support\LedgerService::postExpense($expense->fresh());

            return redirect()->route('expenses.index')->with('success', 'Expense marked as Paid and posted to the ledger.');
        } catch (\Throwable $e) {
            Log::error('Expense markPaid failed', [
                'expense_id' => $expense->id ?? null,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            return back()->with('error', 'Could not mark expense as Paid. ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $expenseQuery = $this->applyTenantScope(Expense::query(), 'expenses');
        $this->applyBranchScope($expenseQuery, 'expenses');
        $expense = $expenseQuery->find($id);
        if (!$expense) {
            return redirect()
                ->route('expenses.index')
                ->with('error', 'Expense not found for the active branch.');
        }

        return redirect()
            ->route('expenses.index')
            ->with('info', 'Expense details are available in the list view.');
    }

    public function edit($id)
    {
        $expenseQuery = $this->applyTenantScope(Expense::query(), 'expenses');
        $this->applyBranchScope($expenseQuery, 'expenses');
        $expense = $expenseQuery->find($id);
        if (!$expense) {
            return redirect()
                ->route('expenses.index')
                ->with('error', 'Expense not found for the active branch.');
        }

        return redirect()
            ->route('expenses.index')
            ->with('info', 'Use the edit action in the list to update this expense.');
    }

    /**
     * Download expense attachment
     */
    public function download($filename)
    {
        $expense = $this->applyTenantScope(Expense::query(), 'expenses')
            ->where('image', $filename)
            ->first();

        if (!$expense) {
            return redirect()->back()->with('error', 'File not found!');
        }

        $filepath = $this->resolveExpenseAttachmentPath($expense->image);

        if ($filepath) {
            return response()->download($filepath);
        }
        
        return redirect()->back()->with('error', 'File not found!');
    }

    public function quickAddBank(Request $request)
    {
        $sessionBranch = $this->getSessionBranchContext();
        if (empty($sessionBranch['id']) && empty($sessionBranch['name'])) {
            return back()->withInput()->with('error', 'Please select a branch before adding a bank/cash source.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'account_number' => 'nullable|string|max:191',
            'balance' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $sessionBranch) {
            if (Schema::hasTable('banks')) {
                $bankAttributes = [
                    'name' => $validated['name'],
                    'account_number' => $validated['account_number'] ?? ('N/A-' . strtolower(preg_replace('/[^a-z0-9]/i', '', $validated['name']))),
                ];
                $bankValues = [
                    'branch' => null,
                    'balance' => (float) ($validated['balance'] ?? 0),
                ];
                if (Schema::hasColumn('banks', 'company_id')) {
                    $bankAttributes['company_id'] = Auth::user()?->company_id ?? session('current_tenant_id');
                }
                if (Schema::hasColumn('banks', 'user_id')) {
                    $bankValues['user_id'] = Auth::id();
                }
                if (Schema::hasColumn('banks', 'branch_id')) {
                    $bankValues['branch_id'] = $sessionBranch['id'];
                }
                if (Schema::hasColumn('banks', 'branch_name')) {
                    $bankValues['branch_name'] = $sessionBranch['name'];
                }

                Bank::updateOrCreate(
                    $bankAttributes,
                    $bankValues
                );
            }

            $accountAttributes = [
                'name' => $validated['name'],
                'type' => 'Asset',
            ];
            $accountValues = [
                'code' => $this->generateAccountCode('AST'),
                'sub_type' => 'Current Asset',
                'description' => 'Bank/Cash account created from expense quick add',
                'opening_balance' => (float) ($validated['balance'] ?? 0),
                'current_balance' => (float) ($validated['balance'] ?? 0),
                'is_active' => true,
            ];
            if (Schema::hasColumn('accounts', 'company_id')) {
                $accountAttributes['company_id'] = Auth::user()?->company_id ?? session('current_tenant_id');
            }
            if (Schema::hasColumn('accounts', 'user_id')) {
                $accountValues['user_id'] = Auth::id();
            }
            if (Schema::hasColumn('accounts', 'branch_id')) {
                $accountValues['branch_id'] = $sessionBranch['id'];
            }
            if (Schema::hasColumn('accounts', 'branch_name')) {
                $accountValues['branch_name'] = $sessionBranch['name'];
            }

            Account::firstOrCreate($accountAttributes, $accountValues);

            return redirect()->route('expenses.index')->with('success', 'Bank/payment source added successfully.');
        });
    }

    public function quickAddCategory(Request $request)
    {
        if (!Schema::hasTable('categories')) {
            return redirect()->route('expenses.index')->with('error', 'Expense categories table is not available on this installation yet.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:191',
        ]);

        return DB::transaction(function () use ($validated) {
            $categoryAttributes = ['name' => $validated['name']];
            $categoryValues = ['description' => null, 'image' => null, 'status' => 1];
            if (Schema::hasColumn('categories', 'company_id')) {
                $categoryAttributes['company_id'] = Auth::user()?->company_id ?: null;
            }
            if (Schema::hasColumn('categories', 'user_id')) {
                $categoryValues['user_id'] = Auth::id();
            }

            $category = Category::firstOrCreate($categoryAttributes, $categoryValues);

            $accountAttributes = [
                'name' => $category->name,
                'type' => 'Expense',
            ];
            $accountValues = [
                'code' => $this->generateAccountCode('EXP'),
                'sub_type' => null,
                'description' => 'Expense account created from category quick add',
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_active' => true,
            ];
            if (Schema::hasColumn('accounts', 'company_id')) {
                $accountAttributes['company_id'] = Auth::user()?->company_id ?: null;
            }
            if (Schema::hasColumn('accounts', 'user_id')) {
                $accountValues['user_id'] = Auth::id();
            }

            Account::firstOrCreate($accountAttributes, $accountValues);

            return redirect()->route('expenses.index')->with('success', 'Expense category added successfully.');
        });
    }

    private function resolveExpenseAccountFromSelector(string $selector): array
    {
        $selector = trim($selector);

        if (str_starts_with($selector, 'cat:')) {
            if (!Schema::hasTable('categories')) {
                abort(422, 'Expense categories are not configured on this installation.');
            }

            $categoryId = (int) str_replace('cat:', '', $selector);
            $category = $this->applyTenantScope(Category::query(), 'categories')->find($categoryId);
            if (!$category) {
                Log::warning('Expense category not found; using fallback account', [
                    'category_id' => $categoryId,
                    'user_id' => Auth::id(),
                ]);
                $fallback = $this->applyTenantScope(Account::where('type', 'Expense'), 'accounts')->first();
                if ($fallback) {
                    return [$fallback, null];
                }
                return [Account::create([
                    'code' => $this->generateExpenseAccountCode(),
                    'name' => 'General Expense',
                    'type' => 'Expense',
                    'sub_type' => null,
                    'description' => 'Auto-created fallback expense account',
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'is_active' => true,
                ]), null];
            }

            $account = $this->applyTenantScope(Account::where('type', 'Expense'), 'accounts')
                ->whereRaw('LOWER(name) = ?', [strtolower((string) $category->name)])
                ->first();

            if (!$account) {
                $payload = [
                    'code' => $this->generateExpenseAccountCode(),
                    'name' => (string) $category->name,
                    'type' => 'Expense',
                    'sub_type' => null,
                    'description' => 'Auto-created from expense category',
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'is_active' => true,
                ];
                if (Schema::hasColumn('accounts', 'company_id')) {
                    $payload['company_id'] = Auth::user()?->company_id ?: null;
                }
                if (Schema::hasColumn('accounts', 'user_id')) {
                    $payload['user_id'] = Auth::id();
                }
                $account = Account::create($payload);
            }

            return [$account, $categoryId];
        }

        $accountId = (int) $selector;
        $account = $this->applyTenantScope(Account::where('id', $accountId)->where('type', 'Expense'), 'accounts')->firstOrFail();

        $categoryId = null;
        if (Schema::hasTable('categories')) {
            $matchedCategory = $this->applyTenantScope(Category::whereRaw('LOWER(name) = ?', [strtolower((string) $account->name)]), 'categories')->first();
            $categoryId = $matchedCategory?->id;
        }

        return [$account, $categoryId];
    }

    private function generateExpenseAccountCode(): string
    {
        do {
            $code = 'EXP-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while ($this->applyTenantScope(Account::where('code', $code), 'accounts')->exists());

        return $code;
    }

    private function generateAccountCode(string $prefix): string
    {
        do {
            $code = strtoupper($prefix) . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while ($this->applyTenantScope(Account::where('code', $code), 'accounts')->exists());

        return $code;
    }

    private function syncBanksToAssetAccounts(): void
    {
        if (!Schema::hasTable('banks') || !Schema::hasTable('accounts')) {
            return;
        }

        $banks = $this->applyTenantScope(Bank::query(), 'banks')->get(['name', 'balance']);
        foreach ($banks as $bank) {
            if (!$bank->name) {
                continue;
            }

            $accountAttributes = ['name' => $bank->name, 'type' => 'Asset'];
            $accountValues = [
                'code' => $this->generateAccountCode('AST'),
                'sub_type' => 'Current Asset',
                'description' => 'Auto-synced from banks table',
                'opening_balance' => (float) ($bank->balance ?? 0),
                'current_balance' => (float) ($bank->balance ?? 0),
                'is_active' => true,
            ];
            if (Schema::hasColumn('accounts', 'company_id')) {
                $accountAttributes['company_id'] = Auth::user()?->company_id ?: null;
            }
            if (Schema::hasColumn('accounts', 'user_id')) {
                $accountValues['user_id'] = Auth::id();
            }

            Account::firstOrCreate($accountAttributes, $accountValues);
        }
    }

    private function resolveExpenseAttachmentPath(?string $filename): ?string
    {
        if (!$filename) {
            return null;
        }

        $publicPath = public_path('assets/img/expenses/' . $filename);
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        $storagePath = storage_path('app/public/expenses/' . $filename);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        return null;
    }

    private function deleteExpenseAttachment(?string $filename): void
    {
        $filepath = $this->resolveExpenseAttachmentPath($filename);
        if ($filepath && file_exists($filepath)) {
            @unlink($filepath);
        }
    }

    /**
     * Get expenses for DataTable
     */
    public function getExpenses(Request $request)
    {
        $expenses = $this->applyTenantScope(Expense::with('creator')->latest(), 'expenses')->get();
        
        return response()->json([
            'data' => $expenses
        ]);
    }
}

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

class ExpenseController extends Controller
{
    public function index()
    {
        $this->syncBanksToAssetAccounts();

        $expenses = Expense::with('creator')->latest()->paginate(15);
        $expenseAccounts = Schema::hasTable('accounts')
            ? Account::where('type', 'Expense')->orderBy('name')->get()
            : collect();
        $assetAccounts = Schema::hasTable('accounts')
            ? Account::where('type', 'Asset')->orderBy('name')->get()
            : collect();
        $categories = Schema::hasTable('categories')
            ? Category::orderBy('name')->get(['id', 'name'])
            : collect();

        $partyOptions = collect();
        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'name')) {
            $partyOptions = $partyOptions->merge(Vendor::query()->orderBy('name')->pluck('name'));
        }
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'customer_name')) {
            $partyOptions = $partyOptions->merge(Customer::query()->orderBy('customer_name')->pluck('customer_name'));
        }
        if (Schema::hasColumn('expenses', 'company_name')) {
            $partyOptions = $partyOptions->merge(
                Expense::query()->whereNotNull('company_name')->orderBy('company_name')->pluck('company_name')
            );
        }
        $partyOptions = $partyOptions->filter()->unique()->values();

        return view('Finance.expenses', compact('expenses', 'expenseAccounts', 'assetAccounts', 'partyOptions', 'categories'));
    }

    public function store(Request $request)
    {
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

        return DB::transaction(function () use ($request) {
            [$expenseAccount, $categoryId] = $this->resolveExpenseAccountFromSelector((string) $request->account_id);
            $paymentAccount = Account::findOrFail($request->payment_account_id);
            $nextId = (int) Expense::max('id') + 1;
            $expenseId = 'EXP-' . date('Y') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);

            $expense = Expense::create([
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
            ]);

            if ($request->status === 'Paid') {
                Transaction::create([
                    'account_id'       => $expenseAccount->id,
                    'transaction_date' => now(),
                    'debit'            => $request->amount,
                    'credit'           => 0,
                    'reference'        => $expense->expense_id,
                    'description'      => 'Expense: ' . $request->company_name,
                    'transaction_type' => 'Expense',
                    'related_id'       => $expense->id,
                    'related_type'     => Expense::class,
                    'user_id'          => Auth::id(),
                ]);

                Transaction::create([
                    'account_id'       => $request->payment_account_id,
                    'transaction_date' => now(),
                    'debit'            => 0,
                    'credit'           => $request->amount,
                    'reference'        => $expense->expense_id,
                    'description'      => 'Expense payment for ' . $request->company_name,
                    'transaction_type' => 'Expense',
                    'related_id'       => $expense->id,
                    'related_type'     => Expense::class,
                    'user_id'          => Auth::id(),
                ]);
            }

            return redirect()->route('expenses.index')->with('success', 'Expense saved successfully.');
        });
    }

private function handleFileUpload($request) {
    if ($request->hasFile('image')) {
        $imageName = time() . '.' . $request->image->extension();
        $request->image->move(public_path('assets/img/expenses'), $imageName);
        return $imageName;
    }
    return null;
}

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:191',
            'email' => 'nullable|email|max:191',
            'reference' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01|max:999999999999.99',
            'account_id' => 'required|string',
            'payment_account_id' => 'required|exists:accounts,id',
            'status' => 'required|string|in:Pending,Paid,Overdue',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        return DB::transaction(function () use ($request, $validated, $expense) {
            [$expenseAccount, $categoryId] = $this->resolveExpenseAccountFromSelector((string) $validated['account_id']);
            $paymentAccount = Account::findOrFail((int) $validated['payment_account_id']);

            if ($request->hasFile('image')) {
                if ($expense->image && file_exists(public_path('assets/img/expenses/' . $expense->image))) {
                    unlink(public_path('assets/img/expenses/' . $expense->image));
                }
                $validated['image'] = $this->handleFileUpload($request);
            }

            $expense->update([
                'company_name'   => $validated['company_name'],
                'reference'      => $validated['reference'] ?? null,
                'email'          => $validated['email'] ?? null,
                'amount'         => $validated['amount'],
                'payment_mode'   => $paymentAccount->name,
                'payment_status' => strtolower($validated['status']) === 'paid' ? 'paid' : 'pending',
                'category'       => $expenseAccount->name,
                'category_id'    => Schema::hasColumn('expenses', 'category_id') ? $categoryId : null,
                'notes'          => $validated['notes'] ?? null,
                'status'         => $validated['status'],
                'image'          => $validated['image'] ?? $expense->image,
            ]);

            // Rebuild ledger entries for this expense to keep accounting accurate.
            Transaction::where('related_id', $expense->id)
                ->where('related_type', Expense::class)
                ->where('transaction_type', 'Expense')
                ->delete();

            if ($validated['status'] === 'Paid') {
                Transaction::create([
                    'account_id'       => $validated['account_id'],
                    'transaction_date' => now(),
                    'debit'            => $validated['amount'],
                    'credit'           => 0,
                    'reference'        => $expense->expense_id,
                    'description'      => 'Expense: ' . $validated['company_name'],
                    'transaction_type' => 'Expense',
                    'related_id'       => $expense->id,
                    'related_type'     => Expense::class,
                    'user_id'          => Auth::id(),
                ]);

                Transaction::create([
                    'account_id'       => $validated['payment_account_id'],
                    'transaction_date' => now(),
                    'debit'            => 0,
                    'credit'           => $validated['amount'],
                    'reference'        => $expense->expense_id,
                    'description'      => 'Expense payment for ' . $validated['company_name'],
                    'transaction_type' => 'Expense',
                    'related_id'       => $expense->id,
                    'related_type'     => Expense::class,
                    'user_id'          => Auth::id(),
                ]);
            }

            return redirect()->route('expenses.index')->with('success', 'Expense updated successfully!');
        });
    }

    public function destroy(Expense $expense)
    {
        Transaction::where('related_id', $expense->id)
            ->where('related_type', Expense::class)
            ->where('transaction_type', 'Expense')
            ->delete();

        // Delete image if exists
        if ($expense->image && file_exists(public_path('assets/img/expenses/' . $expense->image))) {
            unlink(public_path('assets/img/expenses/' . $expense->image));
        }

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully!');
    }

    /**
     * Download expense attachment
     */
    public function download($filename)
    {
        $filepath = public_path('assets/img/expenses/' . $filename);
        
        if (file_exists($filepath)) {
            return response()->download($filepath);
        }
        
        return redirect()->back()->with('error', 'File not found!');
    }

    public function quickAddBank(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'account_number' => 'nullable|string|max:191',
            'balance' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            if (Schema::hasTable('banks')) {
                Bank::updateOrCreate(
                    [
                        'name' => $validated['name'],
                        'account_number' => $validated['account_number'] ?? ('N/A-' . strtolower(preg_replace('/[^a-z0-9]/i', '', $validated['name']))),
                    ],
                    [
                        'branch' => null,
                        'balance' => (float) ($validated['balance'] ?? 0),
                    ]
                );
            }

            Account::firstOrCreate(
                [
                    'name' => $validated['name'],
                    'type' => 'Asset',
                ],
                [
                    'code' => $this->generateAccountCode('AST'),
                    'sub_type' => 'Current Asset',
                    'description' => 'Bank/Cash account created from expense quick add',
                    'opening_balance' => (float) ($validated['balance'] ?? 0),
                    'current_balance' => (float) ($validated['balance'] ?? 0),
                    'is_active' => true,
                ]
            );

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
            $category = Category::firstOrCreate(
                ['name' => $validated['name']],
                ['description' => null, 'image' => null, 'status' => 1]
            );

            Account::firstOrCreate(
                [
                    'name' => $category->name,
                    'type' => 'Expense',
                ],
                [
                    'code' => $this->generateAccountCode('EXP'),
                    'sub_type' => null,
                    'description' => 'Expense account created from category quick add',
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'is_active' => true,
                ]
            );

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
            $category = Category::findOrFail($categoryId);

            $account = Account::where('type', 'Expense')
                ->whereRaw('LOWER(name) = ?', [strtolower((string) $category->name)])
                ->first();

            if (!$account) {
                $account = Account::create([
                    'code' => $this->generateExpenseAccountCode(),
                    'name' => (string) $category->name,
                    'type' => 'Expense',
                    'sub_type' => null,
                    'description' => 'Auto-created from expense category',
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'is_active' => true,
                ]);
            }

            return [$account, $categoryId];
        }

        $accountId = (int) $selector;
        $account = Account::where('id', $accountId)->where('type', 'Expense')->firstOrFail();

        $categoryId = null;
        if (Schema::hasTable('categories')) {
            $matchedCategory = Category::whereRaw('LOWER(name) = ?', [strtolower((string) $account->name)])->first();
            $categoryId = $matchedCategory?->id;
        }

        return [$account, $categoryId];
    }

    private function generateExpenseAccountCode(): string
    {
        do {
            $code = 'EXP-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Account::where('code', $code)->exists());

        return $code;
    }

    private function generateAccountCode(string $prefix): string
    {
        do {
            $code = strtoupper($prefix) . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Account::where('code', $code)->exists());

        return $code;
    }

    private function syncBanksToAssetAccounts(): void
    {
        if (!Schema::hasTable('banks') || !Schema::hasTable('accounts')) {
            return;
        }

        $banks = Bank::query()->get(['name', 'balance']);
        foreach ($banks as $bank) {
            if (!$bank->name) {
                continue;
            }

            Account::firstOrCreate(
                ['name' => $bank->name, 'type' => 'Asset'],
                [
                    'code' => $this->generateAccountCode('AST'),
                    'sub_type' => 'Current Asset',
                    'description' => 'Auto-synced from banks table',
                    'opening_balance' => (float) ($bank->balance ?? 0),
                    'current_balance' => (float) ($bank->balance ?? 0),
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Get expenses for DataTable
     */
    public function getExpenses(Request $request)
    {
        $expenses = Expense::with('creator')->latest()->get();
        
        return response()->json([
            'data' => $expenses
        ]);
    }
}

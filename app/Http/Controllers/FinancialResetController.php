<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;

class FinancialResetController extends Controller
{
    /**
     * Tables whose rows will be deleted (scoped by company_id).
     * Order matters: children before parents to avoid FK constraint errors.
     */
    private const TRANSACTIONAL_TABLES = [
        // Sub-ledger / journal entries
        'transactions',

        // Sales cycle
        'sale_items',
        'sales',
        'invoices',
        'payments',
        'receipts',
        'collection_follow_ups',
        'estimates',
        'quotations',

        // Purchase cycle
        'purchase_items',
        'purchase_transactions',
        'purchases',
        'purchase_returns',
        'supplier_payments',
        'vendor_ledger_transactions',
        'purchase_requisition_items',
        'purchase_requisitions',
        'grn_items',
        'goods_received_notes',
        'landed_costs',

        // Expenses
        'expense_claims',
        'expenses',

        // Payroll
        'payrolls',
        'payroll_runs',

        // Bank
        'bank_statement_lines',
        'bank_statement_imports',
        'cheques',

        // Loans
        'loan_repayments',
        'loans',

        // Fixed assets
        'fixed_asset_depreciations',
        'fixed_assets',

        // Tax
        'tax_filings',

        // Budgets
        'budgets',

        // Manufacturing
        'manufacturing_order_items',
        'manufacturing_orders',

        // Inventory / stock
        'stock_transfer_audits',
        'inventory_history',
        'product_branch_stocks',

        // Intercompany / recurring
        'intercompany_transactions',
        'recurring_transactions',

        // Period close artefacts
        'close_tasks',
        'close_approvals',
        'finance_approvals',
    ];

    /**
     * Show the financial reset confirmation page.
     */
    public function index()
    {
        $this->authoriseSuperAdmin();

        $companyId = Auth::user()->company_id;

        // Count rows per table so the user can see what will be wiped
        $preview = [];
        foreach (self::TRANSACTIONAL_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                $preview[$table] = DB::table($table)->where('company_id', $companyId)->count();
            } elseif (Schema::hasTable($table)) {
                $preview[$table] = DB::table($table)->count();
            }
        }

        $grandTotal = array_sum($preview);

        return view('Finance.reset', compact('preview', 'grandTotal'));
    }

    /**
     * Execute the financial reset after double confirmation.
     */
    public function execute(Request $request)
    {
        $this->authoriseSuperAdmin();

        $request->validate([
            'confirmation_phrase' => ['required', 'string'],
            'password'            => ['required', 'string'],
        ]);

        // Gate 1 – exact phrase
        if ($request->confirmation_phrase !== 'RESET FINANCIAL DATA') {
            return response()->json([
                'success' => false,
                'message' => 'Confirmation phrase does not match. Type exactly: RESET FINANCIAL DATA',
            ], 422);
        }

        // Gate 2 – re-enter password
        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current password is incorrect.',
            ], 422);
        }

        $performer  = Auth::user();
        $companyId  = $performer->company_id;
        $deletedLog = [];
        $errors     = [];

        DB::beginTransaction();
        try {
            // ── Step 1: Disable FK checks for the duration of this transaction ──
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach (self::TRANSACTIONAL_TABLES as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                try {
                    if (Schema::hasColumn($table, 'company_id')) {
                        $count = DB::table($table)->where('company_id', $companyId)->delete();
                    } else {
                        // Table exists but has no company_id scope — skip it for safety
                        $count = 0;
                    }
                    $deletedLog[$table] = $count;
                } catch (\Throwable $e) {
                    $errors[$table] = $e->getMessage();
                    Log::error("FinancialReset: failed on table {$table}: " . $e->getMessage());
                }
            }

            // ── Step 2: Zero-out balances on master data ──

            // Customers – reset balance to 0
            if (Schema::hasTable('customers')) {
                $cols = Schema::getColumnListing('customers');
                $updates = [];
                if (in_array('balance', $cols))          $updates['balance']          = 0;
                if (in_array('opening_balance', $cols))  $updates['opening_balance']  = 0;
                if ($updates) {
                    DB::table('customers')->where('company_id', $companyId)->update($updates);
                    $deletedLog['customers (balance zeroed)'] = DB::table('customers')->where('company_id', $companyId)->count();
                }
            }

            // Suppliers – reset opening_balance to 0
            if (Schema::hasTable('suppliers')) {
                $cols = Schema::getColumnListing('suppliers');
                $updates = [];
                if (in_array('opening_balance', $cols))  $updates['opening_balance']  = 0;
                if (in_array('balance', $cols))          $updates['balance']           = 0;
                if ($updates) {
                    DB::table('suppliers')->where('company_id', $companyId)->update($updates);
                    $deletedLog['suppliers (balance zeroed)'] = DB::table('suppliers')->where('company_id', $companyId)->count();
                }
            }

            // Products – reset stock_quantity to 0
            if (Schema::hasTable('products')) {
                $cols = Schema::getColumnListing('products');
                $updates = [];
                if (in_array('stock_quantity', $cols)) $updates['stock_quantity'] = 0;
                if (in_array('quantity', $cols))       $updates['quantity']       = 0;
                if ($updates) {
                    DB::table('products')->where('company_id', $companyId)->update($updates);
                    $deletedLog['products (stock zeroed)'] = DB::table('products')->where('company_id', $companyId)->count();
                }
            }

            // Banks – reset balance to 0
            if (Schema::hasTable('banks')) {
                $cols = Schema::getColumnListing('banks');
                if (in_array('balance', $cols)) {
                    DB::table('banks')->where('company_id', $companyId)->update(['balance' => 0]);
                    $deletedLog['banks (balance zeroed)'] = DB::table('banks')->where('company_id', $companyId)->count();
                }
            }

            // Accounts (chart of accounts) – restore any soft-deleted accounts
            if (Schema::hasTable('accounts') && Schema::hasColumn('accounts', 'deleted_at')) {
                $restored = DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->whereNotNull('deleted_at')
                    ->update(['deleted_at' => null]);
                if ($restored > 0) {
                    $deletedLog['accounts (soft-deleted restored)'] = $restored;
                }
            }

            // ── Step 3: Re-enable FK checks ──
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            DB::commit();

            $totalRows = array_sum(array_filter($deletedLog, 'is_int'));

            // ── Step 4: Log the action ──
            try {
                ActivityLog::record(
                    'FinancialReset',
                    'reset',
                    "Super-admin executed full financial reset for company #{$companyId}. {$totalRows} total rows removed/zeroed.",
                    [
                        'performed_by'  => $performer->id,
                        'performer_name'=> $performer->name ?? $performer->email,
                        'company_id'    => $companyId,
                        'executed_at'   => now()->toDateTimeString(),
                        'deleted_counts'=> $deletedLog,
                        'errors'        => $errors,
                    ]
                );
            } catch (\Throwable $logEx) {
                Log::warning('FinancialReset: could not write ActivityLog — ' . $logEx->getMessage());
            }

            return response()->json([
                'success'    => true,
                'message'    => 'Financial reset completed successfully.',
                'log'        => $deletedLog,
                'total_rows' => $totalRows,
                'errors'     => $errors,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            Log::error('FinancialReset: rolled back — ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Reset failed and was rolled back: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────

    private function authoriseSuperAdmin(): void
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role ?? '', ['super_admin', 'super admin', 'superadmin'], true)) {
            abort(403, 'Super-admin access required.');
        }
    }
}

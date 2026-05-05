<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseResetController extends Controller
{
    /**
     * Tables wiped during reset (same list as DatabaseReset command).
     * Used only for preview row-counts in the UI.
     */
    private const BUSINESS_TABLES = [
        'manufacturing_order_items', 'bom_items', 'landed_cost_items', 'landed_costs',
        'grn_items', 'goods_received_notes', 'purchase_requisition_items', 'purchase_requisitions',
        'rfq_lines', 'stock_transfer_audits', 'inventory_history', 'product_branch_stocks',
        'asset_maintenance_logs', 'fixed_asset_depreciations', 'fixed_assets',
        'loan_repayments', 'loans', 'bank_statement_lines', 'bank_statement_imports',
        'cheques', 'payroll_runs', 'payrolls', 'leave_requests', 'leave_types', 'attendance',
        'timesheet_entries', 'timesheets', 'project_milestones',
        'close_approvals', 'close_tasks', 'finance_approvals',
        'accounting_branch_audit_findings', 'tax_filings', 'budgets',
        'exchange_rates', 'withholding_rules', 'tax_codes', 'tax_jurisdictions',
        'accounting_periods', 'intercompany_transactions', 'recurring_transactions',
        'collection_follow_ups', 'expense_claims', 'expenses', 'vendor_ledger_transactions',
        'supplier_payments', 'purchase_returns', 'purchase_items', 'purchase_transactions',
        'purchases', 'sale_items', 'product_sales', 'receipts', 'payments', 'invoices',
        'sales', 'quotations', 'estimates', 'price_list_items', 'price_lists',
        'manufacturing_orders', 'bills_of_materials', 'transactions', 'accounts',
        'lots', 'serials', 'barcodes',
        'cost_centers', 'departments', 'employees', 'products', 'categories',
        'banks', 'customers', 'suppliers', 'vendors',
        'deployment_commissions', 'deployment_manager_payouts', 'deployment_companies',
        'deployment_managers', 'platform_payouts', 'active_user_sessions',
        'email_audit_logs', 'activity_logs', 'notifications', 'messages', 'chats',
        'events', 'signatures', 'project_tasks', 'projects',
        'forecasts', 'report_schedules',
        'subscriptions', 'domain_requests', 'domains', 'companies', 'branches', 'tenants',
    ];

    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->authoriseSuperAdmin();

        // Build preview table counts
        $preview    = [];
        $grandTotal = 0;

        foreach (self::BUSINESS_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $count             = DB::table($table)->count();
                $preview[$table]   = $count;
                $grandTotal       += $count;
            }
        }

        // Non-super-admin users that will be deleted
        $userWipeCount = DB::table('users')
            ->where('role', '!=', 'super_admin')
            ->where('role', '!=', 'super admin')
            ->where('role', '!=', 'superadmin')
            ->count();

        return view('SuperAdmin.database-reset', compact('preview', 'grandTotal', 'userWipeCount'));
    }

    public function execute(Request $request): JsonResponse
    {
        $this->authoriseSuperAdmin();

        $request->validate([
            'confirmation_phrase' => 'required|string',
            'password'            => 'required|string',
        ]);

        if ($request->confirmation_phrase !== 'FULL DATABASE RESET') {
            return response()->json(['success' => false, 'message' => 'Confirmation phrase is incorrect. Type exactly: FULL DATABASE RESET'], 422);
        }

        if (! Hash::check($request->password, Auth::user()->password)) {
            return response()->json(['success' => false, 'message' => 'Password is incorrect.'], 422);
        }

        $superAdmin = $this->findSuperAdmin();
        if (! $superAdmin) {
            return response()->json(['success' => false, 'message' => 'Super admin not found in database — aborting.'], 500);
        }

        $log     = [];
        $errors  = [];

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // ── Truncate business tables ──────────────────────────────────
            foreach (self::BUSINESS_TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $log[] = "Truncated: {$table}";
                } else {
                    $log[] = "Skipped (not found): {$table}";
                }
            }

            // ── Clear company-scoped settings ─────────────────────────────
            if (Schema::hasTable('settings')) {
                $n = DB::table('settings')
                    ->where(function ($q) {
                        $q->where('key', 'LIKE', '%_company_%')
                          ->orWhere('key', 'LIKE', '%_branch_%')
                          ->orWhere('key', 'LIKE', '%company_%')
                          ->orWhere('key', 'LIKE', '%branches%');
                    })
                    ->delete();
                $log[] = "Deleted {$n} company-scoped settings rows.";
            }

            // ── Clear sessions ────────────────────────────────────────────
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->truncate();
                $log[] = 'Cleared sessions.';
            }

            // ── Delete non-super-admin users ──────────────────────────────
            $deletedUsers = DB::table('users')
                ->where('role', '!=', 'super_admin')
                ->where('role', '!=', 'super admin')
                ->where('role', '!=', 'superadmin')
                ->where('id', '!=', $superAdmin->id)
                ->delete();
            $log[] = "Deleted {$deletedUsers} non-super-admin user(s).";

            // ── Wipe roles / permissions → reseed ─────────────────────────
            if (Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->truncate();
            }
            if (Schema::hasTable('roles')) {
                DB::table('roles')->truncate();
            }
            if (Schema::hasTable('permissions')) {
                DB::table('permissions')->truncate();
            }
            $log[] = 'Cleared roles, permissions, role_has_permissions — reseeding…';

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // ── Reseed ────────────────────────────────────────────────────
            Artisan::call('db:seed', ['--class' => 'PostResetSeeder', '--force' => true]);
            $log[] = 'PostResetSeeder completed (permissions, roles, plans).';

            // ── Clear super admin role_id (avoids FK orphan) ──────────────
            DB::table('users')->where('id', $superAdmin->id)->update(['role_id' => null]);
            $log[] = 'Super admin role_id cleared (role string "super_admin" preserved).';

            // ── Clear caches ──────────────────────────────────────────────
            Artisan::call('optimize:clear');
            $log[] = 'Application caches cleared.';

            // ── Activity log ──────────────────────────────────────────────
            try {
                ActivityLog::record(
                    'System',
                    'Full Database Reset',
                    'Full database reset executed via web interface. All business data wiped. Super admin preserved.',
                    ['super_admin_id' => $superAdmin->id, 'ip' => $request->ip(), 'reset_at' => now()->toDateTimeString()]
                );
            } catch (\Throwable) {
                // Non-fatal
            }

            return response()->json([
                'success' => true,
                'message' => 'Full database reset completed successfully. Please log in again.',
                'log'     => $log,
            ]);

        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $errors[] = $e->getMessage();
            return response()->json([
                'success' => false,
                'message' => 'Reset failed: ' . $e->getMessage(),
                'log'     => $log,
                'errors'  => $errors,
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function findSuperAdmin(): ?User
    {
        return User::where('role', 'super_admin')
            ->orWhere('role', 'super admin')
            ->orWhere('role', 'superadmin')
            ->first();
    }

    private function authoriseSuperAdmin(): void
    {
        $user = Auth::user();
        $role = $user ? ($user->role ?? '') : '';
        if (! in_array(strtolower(str_replace(' ', '_', $role)), ['super_admin', 'superadmin'], true)) {
            abort(403, 'Super admin access required.');
        }
    }
}

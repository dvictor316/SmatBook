<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * DatabaseReset — Full system wipe.
 *
 * PRESERVES  : super admin user(s), migrations, password_reset_tokens,
 *              personal_access_tokens, failed_jobs, plans, packages.
 *
 * WIPES      : All business / tenant / transactional data.
 *
 * POST-RESET : reseeds permissions, default roles, and plans.
 *
 * Usage (server):
 *   php artisan db:reset
 *   php artisan db:reset --dry-run
 *   php artisan db:reset --force            # skip interactive confirmation
 *   php artisan db:reset --skip-backup      # skip mysqldump step
 */
class DatabaseReset extends Command
{
    protected $signature = 'db:reset
        {--force        : Skip interactive confirmation prompt}
        {--dry-run      : Preview what will be wiped without making changes}
        {--skip-backup  : Skip the mysqldump database backup step}';

    protected $description = 'Full database reset — preserves super admin auth, wipes all business & tenant data, then reseeds defaults.';

    /** Tables that are NEVER touched. */
    private const PRESERVED_TABLES = [
        'migrations',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'users',           // filtered, not truncated
        'roles',           // wiped then reseeded
        'permissions',     // wiped then reseeded
        'role_has_permissions', // wiped then reseeded
        'plans',
        'packages',
        'languages',
        'landing_pages',
        'landing_settings',
        'sessions',        // wiped (force all users to re-login)
    ];

    /**
     * Business / transactional tables to TRUNCATE (order matters for FK).
     * Child tables listed before parent tables.
     */
    private const BUSINESS_TABLES = [
        // ── Transactional children first ──────────────────────────────────
        'manufacturing_order_items',
        'bom_items',
        'landed_cost_items',
        'landed_costs',
        'grn_items',
        'goods_received_notes',
        'purchase_requisition_items',
        'purchase_requisitions',
        'rfq_lines',
        'stock_transfer_audits',
        'inventory_history',
        'product_branch_stocks',
        'asset_maintenance_logs',
        'fixed_asset_depreciations',
        'fixed_assets',
        'loan_repayments',
        'loans',
        'bank_statement_lines',
        'bank_statement_imports',
        'cheques',
        'payroll_runs',
        'payrolls',
        'leave_requests',
        'leave_types',
        'attendance',
        'timesheet_entries',
        'timesheets',
        'project_milestones',
        'close_approvals',
        'close_tasks',
        'finance_approvals',
        'accounting_branch_audit_findings',
        'tax_filings',
        'budgets',
        'exchange_rates',
        'withholding_rules',
        'tax_codes',
        'tax_jurisdictions',
        'accounting_periods',
        'intercompany_transactions',
        'recurring_transactions',
        'collection_follow_ups',
        'expense_claims',
        'expenses',
        'vendor_ledger_transactions',
        'supplier_payments',
        'purchase_returns',
        'purchase_items',
        'purchase_transactions',
        'purchases',
        'sale_items',
        'product_sales',
        'receipts',
        'payments',
        'invoices',
        'sales',
        'quotations',
        'estimates',
        'price_list_items',
        'price_lists',
        'manufacturing_orders',
        'bills_of_materials',
        'transactions',
        'accounts',
        'lots',
        'serials',
        'barcodes',
        // ── Business entities ──────────────────────────────────────────────
        'cost_centers',
        'departments',
        'employees',
        'products',
        'categories',
        'banks',
        'customers',
        'suppliers',
        'vendors',
        // ── Platform / operational logs ────────────────────────────────────
        'deployment_commissions',
        'deployment_manager_payouts',
        'deployment_companies',
        'deployment_managers',
        'platform_payouts',
        'active_user_sessions',
        'email_audit_logs',
        'activity_logs',
        'notifications',
        'messages',
        'chats',
        'events',
        'signatures',
        'project_tasks',
        'projects',
        'forecasts',
        'report_schedules',
        // ── Tenant / company / domain ──────────────────────────────────────
        'subscriptions',
        'domain_requests',
        'domains',
        'companies',
        'branches',
        'tenants',
    ];

    // ──────────────────────────────────────────────────────────────────────────

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════╗');
        $this->line('║           SMATBOOK  —  FULL DATABASE RESET             ║');
        $this->line('╚════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('⚠  DRY-RUN MODE — no changes will be written.');
            $this->newLine();
        }

        // ── 1. Verify super admin exists ──────────────────────────────────
        $superAdmin = $this->findSuperAdmin();
        if (! $superAdmin) {
            $this->error('✗ No super admin user found. Aborting — database cannot be left without a super admin.');
            return self::FAILURE;
        }
        $this->line("  Super admin detected: <fg=green>{$superAdmin->email}</> (ID {$superAdmin->id})");
        $this->newLine();

        // ── 2. Preview counts ─────────────────────────────────────────────
        $this->displayPreview();

        if ($isDryRun) {
            $this->info('Dry-run complete. No changes were made.');
            return self::SUCCESS;
        }

        // ── 3. Confirmation ───────────────────────────────────────────────
        if (! $this->option('force')) {
            if (! $this->confirmReset()) {
                $this->warn('Aborted by user.');
                return self::FAILURE;
            }
        }

        // ── 4. Backup ─────────────────────────────────────────────────────
        if (! $this->option('skip-backup')) {
            $this->createBackup();
        } else {
            $this->warn('  ⚠  Skipping database backup (--skip-backup).');
        }

        // ── 5. Maintenance mode ───────────────────────────────────────────
        $this->line('  Putting application into maintenance mode…');
        Artisan::call('down', ['--secret' => 'smat-reset-bypass']);
        $this->line('  ✓ Maintenance mode active.');

        // ── 6. Execute reset ──────────────────────────────────────────────
        $success = $this->executeReset($superAdmin);

        // ── 7. Restore application ────────────────────────────────────────
        $this->line('  Bringing application back online…');
        Artisan::call('up');
        $this->line('  ✓ Application is live.');

        // ── 8. Clear caches ───────────────────────────────────────────────
        $this->line('  Clearing application caches…');
        Artisan::call('optimize:clear');
        $this->line('  ✓ Caches cleared.');

        $this->newLine();
        if ($success) {
            $this->info('════════════════════════════════════════════════');
            $this->info('  ✓  Full database reset completed successfully.');
            $this->info('  Super admin can now log in at /login');
            $this->info('════════════════════════════════════════════════');
        } else {
            $this->error('  ✗  Reset failed — check the output above.');
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function findSuperAdmin(): ?User
    {
        return User::where('role', 'super_admin')
            ->orWhere('role', 'super admin')
            ->orWhere('role', 'superadmin')
            ->first();
    }

    private function confirmReset(): bool
    {
        $this->error('┌─────────────────────────────────────────────────────────┐');
        $this->error('│  ⚠  WARNING — THIS ACTION IS IRREVERSIBLE               │');
        $this->error('│  All business data, companies, tenants, transactions,    │');
        $this->error('│  customers, products, and logs will be PERMANENTLY       │');
        $this->error('│  deleted. Only the super admin account will remain.      │');
        $this->error('└─────────────────────────────────────────────────────────┘');
        $this->newLine();

        $phrase = $this->ask('Type  FULL DATABASE RESET  to confirm (case-sensitive)');
        if ($phrase !== 'FULL DATABASE RESET') {
            $this->error('Confirmation phrase did not match. Aborting.');
            return false;
        }

        return $this->confirm('Are you absolutely sure you want to wipe all business data?', false);
    }

    private function displayPreview(): void
    {
        $this->line('  <fg=yellow>Tables that will be WIPED:</>');
        $headers = ['Table', 'Row Count'];
        $rows    = [];

        foreach (self::BUSINESS_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $rows[] = [$table, number_format($count)];
            }
        }

        // Settings (company-specific rows)
        if (Schema::hasTable('settings')) {
            $count = DB::table('settings')
                ->where(function ($q) {
                    $q->where('key', 'LIKE', '%_company_%')
                      ->orWhere('key', 'LIKE', '%_branch_%')
                      ->orWhere('key', 'LIKE', '%company_%')
                      ->orWhere('key', 'LIKE', '%branches%');
                })
                ->count();
            if ($count > 0) {
                $rows[] = ['settings (company-scoped rows)', number_format($count)];
            }
        }

        $this->table($headers, $rows);
        $this->newLine();

        $this->line('  <fg=green>Tables that will be PRESERVED:</>');
        $this->line('    migrations, users (super admin only), roles, permissions,');
        $this->line('    role_has_permissions, plans, packages, password_reset_tokens,');
        $this->line('    personal_access_tokens, failed_jobs, languages, landing_pages');
        $this->newLine();

        $this->line('  <fg=cyan>Post-reset reseed:</>');
        $this->line('    ✓ Permissions catalog (full)');
        $this->line('    ✓ Default roles (Administrator, Finance Manager, etc.)');
        $this->line('    ✓ Role → permission mappings');
        $this->line('    ✓ Subscription plans');
        $this->newLine();
    }

    private function createBackup(): void
    {
        $this->line('  Creating MySQL backup…');

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp  = now()->format('Y-m-d_His');
        $backupFile = "{$backupDir}/db-reset-{$timestamp}.sql";

        $host     = config('database.connections.mysql.host', '127.0.0.1');
        $port     = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        // Build secure command — password via env var to avoid shell history exposure
        $cmd = sprintf(
            'MYSQL_PWD=%s mysqldump -h %s -P %s -u %s %s > %s 2>&1',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($backupFile)
        );

        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
            $sizeMb = round(filesize($backupFile) / 1024 / 1024, 2);
            $this->line("  ✓ Backup saved: {$backupFile} ({$sizeMb} MB)");
        } else {
            $this->warn("  ⚠  mysqldump failed or produced empty file. Backup skipped. Continuing…");
            $this->warn("     Output: " . implode(' ', $output));
        }
    }

    private function executeReset(User $superAdmin): bool
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // ── Truncate all business tables ──────────────────────────────
            $truncated = 0;
            foreach (self::BUSINESS_TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $truncated++;
                    $this->line("  ✓ Truncated: {$table}");
                } else {
                    $this->line("  - Skipped (not found): {$table}");
                }
            }

            // ── Clear company-scoped settings ─────────────────────────────
            if (Schema::hasTable('settings')) {
                $deleted = DB::table('settings')
                    ->where(function ($q) {
                        $q->where('key', 'LIKE', '%_company_%')
                          ->orWhere('key', 'LIKE', '%_branch_%')
                          ->orWhere('key', 'LIKE', '%company_%')
                          ->orWhere('key', 'LIKE', '%branches%');
                    })
                    ->delete();
                $this->line("  ✓ Deleted {$deleted} company-scoped settings rows.");
            }

            // ── Clear all sessions (force re-login) ───────────────────────
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->truncate();
                $this->line('  ✓ Cleared sessions table.');
            }

            // ── Remove non-super-admin users ──────────────────────────────
            $deletedUsers = DB::table('users')
                ->where('role', '!=', 'super_admin')
                ->where('role', '!=', 'super admin')
                ->where('role', '!=', 'superadmin')
                ->where('id', '!=', $superAdmin->id)
                ->delete();
            $this->line("  ✓ Deleted {$deletedUsers} non-super-admin user(s).");

            // ── Wipe and reseed roles / permissions ───────────────────────
            $this->line('  Wiping roles and permissions for reseed…');
            if (Schema::hasTable('role_has_permissions')) {
                DB::table('role_has_permissions')->truncate();
            }
            if (Schema::hasTable('roles')) {
                DB::table('roles')->truncate();
            }
            if (Schema::hasTable('permissions')) {
                DB::table('permissions')->truncate();
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->newLine();
            $this->line('  Running post-reset seed…');
            Artisan::call('db:seed', ['--class' => 'PostResetSeeder', '--force' => true], $this->output);

            // ── Restore super admin's role_id if needed ───────────────────
            $adminRole = DB::table('roles')->where('name', 'Administrator')->first();
            if ($adminRole) {
                // Super admin uses role='super_admin' string — don't override that.
                // But if they had a role_id, clear it to avoid FK orphan.
                DB::table('users')->where('id', $superAdmin->id)->update(['role_id' => null]);
                $this->line('  ✓ Super admin role_id cleared (role string "super_admin" preserved).');
            }

            // ── Log the reset event ───────────────────────────────────────
            try {
                ActivityLog::record(
                    'System',
                    'Full Database Reset',
                    'Full database reset executed via Artisan command. All business data wiped. Super admin preserved.',
                    ['super_admin_id' => $superAdmin->id, 'super_admin_email' => $superAdmin->email, 'reset_at' => now()->toDateTimeString()]
                );
            } catch (\Throwable $e) {
                // Non-fatal — activity_logs table may not exist after truncation if timing is off
            }

            // ── Write reset log file ───────────────────────────────────────
            $logFile = storage_path('app/backups/reset-log-' . now()->format('Y-m-d_His') . '.txt');
            file_put_contents($logFile, sprintf(
                "SMATBOOK FULL DATABASE RESET LOG\n" .
                "=================================\n" .
                "Time       : %s\n" .
                "Super Admin: %s (ID %d)\n" .
                "Tables wiped: %d\n" .
                "Performed by: artisan db:reset\n",
                now()->toDateTimeString(),
                $superAdmin->email,
                $superAdmin->id,
                $truncated
            ));
            $this->line("  ✓ Reset log saved: {$logFile}");

            return true;

        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error('  ✗ Reset failed: ' . $e->getMessage());
            $this->error('     File: ' . $e->getFile() . ':' . $e->getLine());
            Artisan::call('up');
            return false;
        }
    }
}

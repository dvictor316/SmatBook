<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;

class FinanceReset extends Command
{
    protected $signature = 'finance:reset
                            {--company= : The company_id to reset (required)}
                            {--force   : Skip interactive confirmation prompt}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Safely wipe all transactional / accounting data for a company and zero out running balances.';

    private const TRANSACTIONAL_TABLES = [
        'transactions',
        'sale_items',
        'sales',
        'invoices',
        'payments',
        'receipts',
        'collection_follow_ups',
        'estimates',
        'quotations',
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
        'expense_claims',
        'expenses',
        'payrolls',
        'payroll_runs',
        'bank_statement_lines',
        'bank_statement_imports',
        'cheques',
        'loan_repayments',
        'loans',
        'fixed_asset_depreciations',
        'fixed_assets',
        'tax_filings',
        'budgets',
        'manufacturing_order_items',
        'manufacturing_orders',
        'stock_transfer_audits',
        'inventory_history',
        'product_branch_stocks',
        'intercompany_transactions',
        'recurring_transactions',
        'close_tasks',
        'close_approvals',
        'finance_approvals',
    ];

    public function handle(): int
    {
        $companyId = (int) $this->option('company');
        $dryRun    = (bool) $this->option('dry-run');
        $force     = (bool) $this->option('force');

        if (!$companyId) {
            $this->error('--company=ID is required.');
            return self::FAILURE;
        }

        // Verify company exists
        if (!Schema::hasTable('companies') || !DB::table('companies')->where('id', $companyId)->exists()) {
            $this->error("Company #{$companyId} not found.");
            return self::FAILURE;
        }

        $companyName = DB::table('companies')->where('id', $companyId)->value('name') ?? "#{$companyId}";

        $this->warn("======================================================");
        $this->warn("  FINANCIAL RESET for company: {$companyName} (ID={$companyId})");
        $this->warn("======================================================");

        if ($dryRun) {
            $this->info('[DRY-RUN MODE — nothing will be deleted]');
        }

        // Preview counts
        $preview = [];
        foreach (self::TRANSACTIONAL_TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                $count = DB::table($table)->where('company_id', $companyId)->count();
                if ($count > 0 || !$dryRun) {
                    $preview[$table] = $count;
                }
            }
        }

        $grandTotal = array_sum($preview);

        $this->table(['Table', 'Rows to Delete'], array_map(
            fn($t, $c) => [$t, number_format($c)],
            array_keys($preview), $preview
        ));

        $this->line('');
        $this->line("Grand total: <fg=red>{$grandTotal} rows</> will be deleted.");

        if ($dryRun) {
            $this->info('Dry-run complete. No changes made.');
            return self::SUCCESS;
        }

        if (!$force) {
            $confirmed = $this->confirm(
                "⚠️  This CANNOT be undone. Delete all financial data for \"{$companyName}\"?",
                false
            );
            if (!$confirmed) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }

            $phrase = $this->ask('Type exactly  RESET FINANCIAL DATA  to proceed');
            if ($phrase !== 'RESET FINANCIAL DATA') {
                $this->error('Phrase does not match. Aborted.');
                return self::FAILURE;
            }
        }

        $this->line('Starting reset...');
        $deletedLog = [];
        $errors     = [];

        DB::beginTransaction();
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach (self::TRANSACTIONAL_TABLES as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                try {
                    if (Schema::hasColumn($table, 'company_id')) {
                        $count = DB::table($table)->where('company_id', $companyId)->delete();
                        $deletedLog[$table] = $count;
                        if ($count > 0) {
                            $this->line("  Deleted <fg=yellow>{$count}</> rows from <fg=cyan>{$table}</>");
                        }
                    }
                } catch (\Throwable $e) {
                    $errors[$table] = $e->getMessage();
                    $this->error("  ERROR on {$table}: " . $e->getMessage());
                    Log::error("FinanceReset CLI: failed on {$table}: " . $e->getMessage());
                }
            }

            // Zero-out master data balances
            $this->zeroBalances($companyId, $deletedLog);

            // Restore soft-deleted accounts
            if (Schema::hasTable('accounts') && Schema::hasColumn('accounts', 'deleted_at')) {
                $restored = DB::table('accounts')
                    ->where('company_id', $companyId)
                    ->whereNotNull('deleted_at')
                    ->update(['deleted_at' => null]);
                if ($restored > 0) {
                    $deletedLog['accounts (soft-deleted restored)'] = $restored;
                    $this->line("  Restored <fg=yellow>{$restored}</> soft-deleted account(s).");
                }
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::commit();

            $totalRows = array_sum(array_filter($deletedLog, 'is_int'));

            // Log it
            try {
                ActivityLog::record(
                    'FinancialReset',
                    'reset',
                    "Artisan CLI executed full financial reset for company #{$companyId}. {$totalRows} rows removed/zeroed.",
                    [
                        'source'         => 'artisan finance:reset',
                        'company_id'     => $companyId,
                        'executed_at'    => now()->toDateTimeString(),
                        'deleted_counts' => $deletedLog,
                        'errors'         => $errors,
                    ]
                );
            } catch (\Throwable $logEx) {
                $this->warn('Could not write ActivityLog: ' . $logEx->getMessage());
            }

            $this->newLine();
            $this->info("✔ Financial reset complete. {$totalRows} total rows removed/zeroed.");

            if (!empty($errors)) {
                $this->warn('Some tables had errors (see above). They were skipped but the rest committed.');
            }

            return self::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error('Reset failed and was rolled back: ' . $e->getMessage());
            Log::error('FinanceReset CLI: rolled back — ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function zeroBalances(int $companyId, array &$log): void
    {
        $checks = [
            'customers' => ['balance', 'opening_balance'],
            'suppliers' => ['opening_balance', 'balance'],
            'banks'     => ['balance'],
        ];

        foreach ($checks as $table => $fields) {
            if (!Schema::hasTable($table)) continue;
            $cols    = Schema::getColumnListing($table);
            $updates = [];
            foreach ($fields as $f) {
                if (in_array($f, $cols)) $updates[$f] = 0;
            }
            if ($updates && Schema::hasColumn($table, 'company_id')) {
                DB::table($table)->where('company_id', $companyId)->update($updates);
                $log["{$table} (balance zeroed)"] = DB::table($table)->where('company_id', $companyId)->count();
                $this->line("  Zeroed balance fields on <fg=cyan>{$table}</>");
            }
        }

        // Products stock
        if (Schema::hasTable('products')) {
            $cols    = Schema::getColumnListing('products');
            $updates = [];
            if (in_array('stock_quantity', $cols)) $updates['stock_quantity'] = 0;
            if (in_array('quantity', $cols))       $updates['quantity']       = 0;
            if ($updates && Schema::hasColumn('products', 'company_id')) {
                DB::table('products')->where('company_id', $companyId)->update($updates);
                $log['products (stock zeroed)'] = DB::table('products')->where('company_id', $companyId)->count();
                $this->line('  Zeroed stock quantities on <fg=cyan>products</>');
            }
        }
    }
}

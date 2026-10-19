<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class FinancialDataReset
{
    private const TRANSACTIONAL_TABLES = [
        'transactions',
        'sale_items',
        'invoices',
        'payments',
        'receipts',
        'sales',
        'collection_follow_ups',
        'estimates',
        'quotations',
        'purchase_items',
        'purchase_transactions',
        'purchase_returns',
        'supplier_payments',
        'purchases',
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

    private const MASTER_BALANCES = [
        'customers' => ['balance', 'opening_balance', 'wallet_balance'],
        'suppliers' => ['balance', 'opening_balance'],
        'vendors' => ['balance', 'opening_balance'],
        'banks' => ['balance', 'opening_balance'],
        'accounts' => ['balance', 'opening_balance', 'current_balance'],
        'products' => ['stock', 'stock_quantity', 'quantity', 'stock_cartons', 'stock_rolls', 'stock_units'],
    ];

    // Older child tables inherit ownership from their parent document.
    private const PARENTS = [
        'sale_items' => ['sale_id', 'sales'],
        'payments' => ['sale_id', 'sales'],
        'receipts' => ['customer_id', 'customers'],
        'invoices' => ['customer_id', 'customers'],
        'purchase_items' => ['purchase_id', 'purchases'],
        'purchase_returns' => ['purchase_id', 'purchases'],
        'supplier_payments' => ['purchase_id', 'purchases'],
        'purchase_requisition_items' => ['purchase_requisition_id', 'purchase_requisitions'],
        'grn_items' => ['grn_id', 'goods_received_notes'],
        'bank_statement_lines' => ['bank_statement_import_id', 'bank_statement_imports'],
        'loan_repayments' => ['loan_id', 'loans'],
        'fixed_asset_depreciations' => ['fixed_asset_id', 'fixed_assets'],
        'manufacturing_order_items' => ['manufacturing_order_id', 'manufacturing_orders'],
    ];

    public function scopedQuery(string $table, int $companyId): Builder
    {
        $query = DB::table($table);
        $hasCompany = Schema::hasColumn($table, 'company_id');
        $ownerColumns = array_values(array_filter(['user_id', 'created_by'], fn ($column) => Schema::hasColumn($table, $column)));
        $parent = self::PARENTS[$table] ?? null;
        if ($parent && (! Schema::hasColumn($table, $parent[0]) || ! Schema::hasTable($parent[1]))) {
            $parent = null;
        }

        if (! $hasCompany && $ownerColumns === [] && ! $parent) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $scope) use ($table, $companyId, $hasCompany, $ownerColumns, $parent) {
            if ($hasCompany) {
                $scope->where($table.'.company_id', $companyId);
            }
            if ($ownerColumns !== [] || $parent) {
                $legacyScope = function (Builder $legacy) use ($table, $companyId, $hasCompany, $ownerColumns, $parent) {
                    if ($hasCompany) {
                        $legacy->whereNull($table.'.company_id');
                    }
                    $legacy->where(function (Builder $ownership) use ($table, $companyId, $ownerColumns, $parent) {
                        foreach ($ownerColumns as $column) {
                            $ownership->orWhereIn($table.'.'.$column, DB::table('users')->select('id')->where('company_id', $companyId));
                        }
                        if ($parent) {
                            $ownership->orWhereIn($table.'.'.$parent[0], $this->scopedQuery($parent[1], $companyId)->select('id'));
                        }
                    });
                };
                $hasCompany ? $scope->orWhere($legacyScope) : $scope->where($legacyScope);
            }
        });
    }

    public function preview(int $companyId): array
    {
        $this->validateCompany($companyId);
        $counts = [];
        foreach (self::TRANSACTIONAL_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $counts[$table] = $this->scopedQuery($table, $companyId)->count();
            }
        }

        return $counts;
    }

    public function execute(int $companyId): array
    {
        $this->validateCompany($companyId);

        return Schema::withoutForeignKeyConstraints(function () use ($companyId) {
            return DB::transaction(function () use ($companyId) {
                $log = [];
                foreach (self::TRANSACTIONAL_TABLES as $table) {
                    if (Schema::hasTable($table)) {
                        // A failure must roll back the entire reset, including opening balances.
                        $log[$table] = $this->scopedQuery($table, $companyId)->delete();
                    }
                }
                foreach (self::MASTER_BALANCES as $table => $columns) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }
                    $updates = [];
                    foreach ($columns as $column) {
                        if (Schema::hasColumn($table, $column)) {
                            $updates[$column] = 0;
                        }
                    }
                    if ($updates !== []) {
                        $log[$table.' (balances zeroed)'] = $this->scopedQuery($table, $companyId)->update($updates);
                    }
                }
                if (Schema::hasTable('accounts') && Schema::hasColumn('accounts', 'deleted_at')) {
                    $log['accounts (soft-deleted restored)'] = $this->scopedQuery('accounts', $companyId)
                        ->whereNotNull('deleted_at')->update(['deleted_at' => null]);
                }

                return $log;
            });
        });
    }

    private function validateCompany(int $companyId): void
    {
        if ($companyId <= 0 || ! DB::table('companies')->where('id', $companyId)->exists()) {
            throw new InvalidArgumentException('Select a valid business before resetting financial data.');
        }
    }
}

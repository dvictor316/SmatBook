<?php

namespace App\Support;

use App\Models\Payroll;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaxReturnPreparationService
{
    public function prepare(string $start, string $end, array $context = []): array
    {
        $filingType = strtolower((string) ($context['filing_type'] ?? 'vat'));
        $companyId = (int) ($context['company_id'] ?? auth()->user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($context['user_id'] ?? auth()->id() ?? 0);
        $branchScope = (string) ($context['branch_scope'] ?? session('active_branch_scope', 'branch'));
        $branchId = trim((string) ($context['branch_id'] ?? session('active_branch_id', '')));
        $branchName = trim((string) ($context['branch_name'] ?? session('active_branch_name', '')));
        $currencyCode = (string) ($context['currency_code'] ?? 'NGN');

        $salesTax = 0.0;
        $salesTaxable = 0.0;
        $purchaseTax = 0.0;
        $purchaseTaxable = 0.0;
        $withholdingPayable = 0.0;
        $withholdingReceivable = 0.0;
        $payeAmount = 0.0;

        if (Schema::hasTable('sales')) {
            $salesDateColumn = Schema::hasColumn('sales', 'order_date')
                ? 'order_date'
                : (Schema::hasColumn('sales', 'date') ? 'date' : 'created_at');

            $salesQuery = DB::table('sales')->whereBetween(DB::raw("DATE({$salesDateColumn})"), [$start, $end]);
            $this->applyScope($salesQuery, 'sales', $companyId, $userId, $branchScope, $branchId, $branchName);

            $salesTaxColumn = Schema::hasColumn('sales', 'tax_amount') ? 'tax_amount' : (Schema::hasColumn('sales', 'tax') ? 'tax' : null);
            $salesTotalColumn = Schema::hasColumn('sales', 'total_amount') ? 'total_amount' : (Schema::hasColumn('sales', 'total') ? 'total' : null);

            if ($salesTaxColumn) {
                $salesTax = (float) $salesQuery->sum($salesTaxColumn);
            }
            if ($salesTaxColumn && $salesTotalColumn) {
                $salesTaxable = (float) DB::table('sales')
                    ->whereBetween(DB::raw("DATE({$salesDateColumn})"), [$start, $end])
                    ->tap(fn ($q) => $this->applyScope($q, 'sales', $companyId, $userId, $branchScope, $branchId, $branchName))
                    ->sum(DB::raw("MAX(COALESCE({$salesTotalColumn}, 0) - COALESCE({$salesTaxColumn}, 0), 0)"));
            }
        }

        if (Schema::hasTable('purchases')) {
            $purchaseDateColumn = Schema::hasColumn('purchases', 'purchase_date')
                ? 'purchase_date'
                : (Schema::hasColumn('purchases', 'date') ? 'date' : 'created_at');

            $purchaseQuery = DB::table('purchases')->whereBetween(DB::raw("DATE({$purchaseDateColumn})"), [$start, $end]);
            $this->applyScope($purchaseQuery, 'purchases', $companyId, $userId, $branchScope, $branchId, $branchName);

            $purchaseTaxColumn = Schema::hasColumn('purchases', 'tax_amount') ? 'tax_amount' : (Schema::hasColumn('purchases', 'tax') ? 'tax' : null);
            $purchaseTotalColumn = Schema::hasColumn('purchases', 'total_amount') ? 'total_amount' : (Schema::hasColumn('purchases', 'amount') ? 'amount' : null);

            if ($purchaseTaxColumn) {
                $purchaseTax = (float) $purchaseQuery->sum($purchaseTaxColumn);
            }
            if ($purchaseTaxColumn && $purchaseTotalColumn) {
                $purchaseTaxable = (float) DB::table('purchases')
                    ->whereBetween(DB::raw("DATE({$purchaseDateColumn})"), [$start, $end])
                    ->tap(fn ($q) => $this->applyScope($q, 'purchases', $companyId, $userId, $branchScope, $branchId, $branchName))
                    ->sum(DB::raw("MAX(COALESCE({$purchaseTotalColumn}, 0) - COALESCE({$purchaseTaxColumn}, 0), 0)"));
            }
        }

        if (Schema::hasTable('transactions')) {
            $whtQuery = DB::table('transactions')
                ->join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->whereNull('transactions.deleted_at')
                ->whereBetween(DB::raw('DATE(transactions.transaction_date)'), [$start, $end])
                ->where(function ($query) {
                    $query->whereRaw('LOWER(accounts.name) like ?', ['%withholding%'])
                        ->orWhereRaw('LOWER(accounts.name) like ?', ['%wht%'])
                        ->orWhereRaw('LOWER(accounts.code) like ?', ['%wht%']);
                });
            $this->applyScope($whtQuery, 'transactions', $companyId, $userId, $branchScope, $branchId, $branchName);

            $withholdingPayable = (float) (clone $whtQuery)->sum('transactions.credit');
            $withholdingReceivable = (float) (clone $whtQuery)->sum('transactions.debit');
        }

        if (Schema::hasTable('payrolls')) {
            $payrollDateColumn = Schema::hasColumn('payrolls', 'payroll_month')
                ? 'payroll_month'
                : (Schema::hasColumn('payrolls', 'created_at') ? 'created_at' : null);
            if ($payrollDateColumn) {
                $payrollQuery = Payroll::query()->whereBetween(DB::raw("DATE({$payrollDateColumn})"), [$start, $end]);
                $this->applyScope($payrollQuery, 'payrolls', $companyId, $userId, $branchScope, $branchId, $branchName);
                $payrolls = $payrollQuery->get();
                $payeAmount = (float) $payrolls->sum(function ($payroll) {
                    $deductions = json_decode((string) ($payroll->deductions_json ?? '[]'), true) ?: [];
                    return collect($deductions)
                        ->filter(fn ($item) => str_contains(strtolower((string) ($item['name'] ?? '')), 'paye'))
                        ->sum(fn ($item) => (float) ($item['amount'] ?? 0));
                });
            }
        }

        $lines = [];
        if (in_array($filingType, ['vat', 'all'], true)) {
            $lines[] = [
                'line_key' => 'output_vat',
                'label' => 'Output VAT / Sales Tax',
                'tax_type' => 'vat',
                'taxable_base' => round($salesTaxable, 2),
                'tax_amount' => round($salesTax, 2),
                'adjustment_amount' => 0.0,
                'credit_amount' => 0.0,
                'net_amount' => round($salesTax, 2),
            ];
            $lines[] = [
                'line_key' => 'input_vat',
                'label' => 'Input VAT / Purchase Tax',
                'tax_type' => 'vat',
                'taxable_base' => round($purchaseTaxable, 2),
                'tax_amount' => round($purchaseTax, 2),
                'adjustment_amount' => 0.0,
                'credit_amount' => round($purchaseTax, 2),
                'net_amount' => round(0 - $purchaseTax, 2),
            ];
        }

        if (in_array($filingType, ['withholding', 'all'], true)) {
            $lines[] = [
                'line_key' => 'withholding_payable',
                'label' => 'Withholding Tax Payable',
                'tax_type' => 'withholding',
                'taxable_base' => round($withholdingPayable + $withholdingReceivable, 2),
                'tax_amount' => round($withholdingPayable, 2),
                'adjustment_amount' => 0.0,
                'credit_amount' => round($withholdingReceivable, 2),
                'net_amount' => round($withholdingPayable - $withholdingReceivable, 2),
            ];
        }

        if (in_array($filingType, ['paye', 'all'], true)) {
            $lines[] = [
                'line_key' => 'paye',
                'label' => 'PAYE',
                'tax_type' => 'paye',
                'taxable_base' => 0.0,
                'tax_amount' => round($payeAmount, 2),
                'adjustment_amount' => 0.0,
                'credit_amount' => 0.0,
                'net_amount' => round($payeAmount, 2),
            ];
        }

        $totalTax = (float) collect($lines)->sum('tax_amount');
        $credits = (float) collect($lines)->sum('credit_amount');
        $adjustments = (float) collect($lines)->sum('adjustment_amount');
        $taxDue = $filingType === 'vat'
            ? max(0, round($salesTax - $purchaseTax, 2))
            : max(0, round($totalTax - $credits + $adjustments, 2));

        return [
            'period_start' => $start,
            'period_end' => $end,
            'filing_type' => $filingType,
            'currency_code' => $currencyCode,
            'sales_taxable' => round($salesTaxable, 2),
            'purchase_taxable' => round($purchaseTaxable, 2),
            'sales_tax' => round($salesTax, 2),
            'purchase_tax' => round($purchaseTax, 2),
            'withholding_payable' => round($withholdingPayable, 2),
            'withholding_receivable' => round($withholdingReceivable, 2),
            'paye_tax' => round($payeAmount, 2),
            'total_taxable' => round($salesTaxable + $purchaseTaxable, 2),
            'total_tax' => round($totalTax, 2),
            'tax_due' => round($taxDue, 2),
            'tax_credit' => round($credits, 2),
            'tax_refund' => round(max(0, $credits - $totalTax), 2),
            'adjustments_total' => round($adjustments, 2),
            'credits_total' => round($credits, 2),
            'lines' => $lines,
        ];
    }

    private function applyScope($query, string $table, int $companyId, int $userId, string $branchScope, string $branchId, string $branchName): void
    {
        if ($companyId > 0 && Schema::hasColumn($table, 'company_id')) {
            $query->where("{$table}.company_id", $companyId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'user_id')) {
            $query->where("{$table}.user_id", $userId);
        } elseif ($userId > 0 && Schema::hasColumn($table, 'created_by')) {
            $query->where("{$table}.created_by", $userId);
        }

        if ($branchScope === 'all') {
            return;
        }

        if ($branchId === '' && $branchName === '') {
            return;
        }

        $query->where(function ($scoped) use ($table, $branchId, $branchName) {
            $matched = false;

            if ($branchId !== '' && Schema::hasColumn($table, 'branch_id')) {
                $scoped->where("{$table}.branch_id", $branchId);
                $matched = true;
            }

            if ($branchName !== '' && Schema::hasColumn($table, 'branch_name')) {
                $method = $matched ? 'orWhere' : 'where';
                $scoped->{$method}("{$table}.branch_name", $branchName);
            }
        });
    }
}

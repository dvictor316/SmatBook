<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Bank;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BankBalanceManagementService
{
    public function diagnosticsForAccounts(Collection $accounts): array
    {
        $diagnostics = [];

        foreach ($accounts as $account) {
            if (!$account instanceof Account) {
                continue;
            }

            $diagnostic = $this->buildDiagnostic($account);
            if ($diagnostic !== null) {
                $diagnostics[$account->id] = $diagnostic;
            }
        }

        return $diagnostics;
    }

    public function buildDiagnostic(Account $account): ?array
    {
        if (!$this->isRelevantAccount($account)) {
            return null;
        }

        $linkedBank = $this->findLinkedBank($account);
        $paymentUsageCount = $this->paymentUsageCount($account);

        if (!$linkedBank && $paymentUsageCount === 0) {
            return null;
        }

        $transactionsQuery = Transaction::query()->where('account_id', $account->id);
        $transactionCount = (int) (clone $transactionsQuery)->count();
        $totalDebit = round((float) (clone $transactionsQuery)->sum('debit'), 2);
        $totalCredit = round((float) (clone $transactionsQuery)->sum('credit'), 2);
        $ledgerMovement = round($this->signedBalanceForAccountType($account, $totalDebit, $totalCredit), 2);
        $openingBalance = round((float) ($account->opening_balance ?? 0), 2);
        $derivedLedgerBalance = round($openingBalance + $ledgerMovement, 2);
        $storedCurrentBalance = round((float) ($account->getRawOriginal('current_balance') ?? $account->current_balance ?? 0), 2);
        $cachedDelta = round($storedCurrentBalance - $derivedLedgerBalance, 2);

        $journalQuery = Transaction::query()
            ->where('account_id', $account->id)
            ->where('transaction_type', Transaction::TYPE_JOURNAL);

        $journalDebit = round((float) (clone $journalQuery)->sum('debit'), 2);
        $journalCredit = round((float) (clone $journalQuery)->sum('credit'), 2);
        $journalImpact = round($this->signedBalanceForAccountType($account, $journalDebit, $journalCredit), 2);

        $openingJournalQuery = Transaction::query()
            ->where('account_id', $account->id)
            ->where('transaction_type', Transaction::TYPE_OPENING_BALANCE);

        $openingJournalDebit = round((float) (clone $openingJournalQuery)->sum('debit'), 2);
        $openingJournalCredit = round((float) (clone $openingJournalQuery)->sum('credit'), 2);
        $openingJournalImpact = round($this->signedBalanceForAccountType($account, $openingJournalDebit, $openingJournalCredit), 2);

        $statementSummary = $this->statementSummary($linkedBank);
        $bankBalance = round((float) ($linkedBank?->balance ?? 0), 2);

        return [
            'account_id' => $account->id,
            'account_name' => (string) $account->name,
            'linked_bank_id' => $linkedBank?->id,
            'linked_bank_name' => $linkedBank?->name,
            'linked_bank_balance' => $bankBalance,
            'payment_usage_count' => $paymentUsageCount,
            'transaction_count' => $transactionCount,
            'opening_balance' => $openingBalance,
            'ledger_movement' => $ledgerMovement,
            'derived_ledger_balance' => $derivedLedgerBalance,
            'stored_current_balance' => $storedCurrentBalance,
            'cached_balance_delta' => $cachedDelta,
            'journal_entry_count' => (int) (clone $journalQuery)->count(),
            'journal_entry_impact' => $journalImpact,
            'opening_journal_count' => (int) (clone $openingJournalQuery)->count(),
            'opening_journal_impact' => $openingJournalImpact,
            'statement_import_count' => $statementSummary['import_count'],
            'statement_line_count' => $statementSummary['line_count'],
            'statement_unmatched_count' => $statementSummary['unmatched_count'],
            'statement_latest_closing_balance' => $statementSummary['latest_closing_balance'],
            'sources' => [
                [
                    'label' => 'Opening balance',
                    'value' => $openingBalance,
                    'detail' => 'Stored on the chart-of-accounts record.',
                ],
                [
                    'label' => 'Ledger transactions',
                    'value' => $ledgerMovement,
                    'detail' => "{$transactionCount} posted transaction(s) for this account.",
                ],
                [
                    'label' => 'Payment channel balance',
                    'value' => $bankBalance,
                    'detail' => $linkedBank ? 'Stored on the linked bank/payment channel record.' : 'No linked bank/payment channel record found.',
                ],
                [
                    'label' => 'Journal entries',
                    'value' => $journalImpact,
                    'detail' => 'Manual or system journal entries posted to this account.',
                ],
                [
                    'label' => 'Reconciliation / reserve diagnostics',
                    'value' => $statementSummary['latest_closing_balance'],
                    'detail' => $statementSummary['detail'],
                ],
                [
                    'label' => 'Cached / stored balance field',
                    'value' => $storedCurrentBalance,
                    'detail' => 'Stored current_balance compared against the ledger-derived balance.',
                ],
            ],
            'can_direct_reset' => $transactionCount === 0,
            'requires_journal_zero' => abs($derivedLedgerBalance) >= 0.01,
            'blocks_manual_edit' => $transactionCount > 0,
            'scope' => [
                'company_id' => (int) ($account->company_id ?? 0),
                'branch_id' => (string) ($account->branch_id ?? ''),
                'branch_name' => (string) ($account->branch_name ?? ''),
            ],
        ];
    }

    public function zeroViaJournal(Account $account, string $reason, bool $clearLinkedBankBalance = true): array
    {
        $diagnostic = $this->buildDiagnostic($account);
        if ($diagnostic === null) {
            throw ValidationException::withMessages([
                'account_id' => 'This account is not eligible for bank balance management.',
            ]);
        }

        $balance = (float) $diagnostic['derived_ledger_balance'];
        if (abs($balance) < 0.01) {
            throw ValidationException::withMessages([
                'account_id' => 'This account is already at zero on a ledger basis.',
            ]);
        }

        $branchId = trim((string) ($account->branch_id ?: session('active_branch_id', '')));
        $branchName = trim((string) ($account->branch_name ?: session('active_branch_name', '')));

        if ($branchId === '' && $branchName === '') {
            throw ValidationException::withMessages([
                'branch_id' => 'An active branch is required before posting a balancing journal entry.',
            ]);
        }

        $companyId = (int) ($account->company_id ?? Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) (Auth::id() ?? 0);
        $offsetAccount = $this->ensureOffsetAccount($companyId, $userId, $branchId, $branchName);
        $amount = round(abs($balance), 2);
        $reference = 'BANKZERO-' . $account->id . '-' . now()->format('YmdHis');
        $description = 'Bank balance zeroing journal for ' . $account->name . '. Reason: ' . $reason;

        DB::transaction(function () use ($account, $offsetAccount, $balance, $amount, $reference, $description, $companyId, $userId, $branchId, $branchName, $clearLinkedBankBalance) {
            $lines = $this->journalLinesForBalance($account, $offsetAccount, $balance, $amount);

            foreach ($lines as $line) {
                Transaction::create([
                    'account_id' => $line['account_id'],
                    'transaction_date' => now()->toDateString(),
                    'reference' => $reference,
                    'description' => $description,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'balance' => 0,
                    'transaction_type' => Transaction::TYPE_JOURNAL,
                    'related_id' => null,
                    'related_type' => null,
                    'user_id' => $userId ?: null,
                    'company_id' => $companyId ?: null,
                    'branch_id' => $branchId !== '' ? $branchId : null,
                    'branch_name' => $branchName !== '' ? $branchName : null,
                ]);
            }

            $account->refresh()->updateBalance();

            if ($clearLinkedBankBalance) {
                $linkedBank = $this->findLinkedBank($account);
                if ($linkedBank) {
                    $linkedBank->update(['balance' => 0]);
                }
            }
        });

        $updated = $account->fresh();
        $updatedDiagnostic = $this->buildDiagnostic($updated);

        $this->recordAudit($updated, 'bank_balance_zero_journal', $reason, $diagnostic, $updatedDiagnostic, [
            'reference' => $reference,
            'offset_account_id' => $offsetAccount->id,
            'offset_account_name' => $offsetAccount->name,
            'mode' => 'journal',
        ]);

        return [
            'reference' => $reference,
            'old_balance' => $diagnostic['derived_ledger_balance'],
            'new_balance' => $updatedDiagnostic['derived_ledger_balance'] ?? 0,
        ];
    }

    public function directClear(Account $account, string $reason): array
    {
        $diagnostic = $this->buildDiagnostic($account);
        if ($diagnostic === null) {
            throw ValidationException::withMessages([
                'account_id' => 'This account is not eligible for bank balance management.',
            ]);
        }

        if (!$diagnostic['can_direct_reset']) {
            throw ValidationException::withMessages([
                'account_id' => 'Direct clearing is only allowed when no ledger transactions exist for this account.',
            ]);
        }

        $linkedBank = $this->findLinkedBank($account);

        DB::transaction(function () use ($account, $linkedBank) {
            $account->update([
                'opening_balance' => 0,
                'current_balance' => 0,
            ]);

            if ($linkedBank) {
                $linkedBank->update(['balance' => 0]);

                if (Schema::hasTable('bank_statement_lines')) {
                    BankStatementLine::query()
                        ->where('bank_id', $linkedBank->id)
                        ->delete();
                }

                if (Schema::hasTable('bank_statement_imports')) {
                    BankStatementImport::query()
                        ->where('bank_id', $linkedBank->id)
                        ->delete();
                }
            }
        });

        $updated = $account->fresh();
        $updatedDiagnostic = $this->buildDiagnostic($updated);

        $this->recordAudit($updated, 'bank_balance_direct_clear', $reason, $diagnostic, $updatedDiagnostic, [
            'mode' => 'direct_clear',
            'linked_bank_id' => $linkedBank?->id,
            'linked_bank_name' => $linkedBank?->name,
        ]);

        return [
            'old_balance' => $diagnostic['derived_ledger_balance'],
            'new_balance' => $updatedDiagnostic['derived_ledger_balance'] ?? 0,
        ];
    }

    public function isRelevantAccount(Account $account): bool
    {
        if (strcasecmp((string) ($account->type ?? ''), Account::TYPE_ASSET) !== 0) {
            return false;
        }

        $subType = strtolower(trim((string) ($account->sub_type ?? '')));
        $name = strtolower(trim((string) ($account->name ?? '')));

        return in_array($subType, [
            'bank',
            'cash',
            'cash and bank',
            'cash & bank',
            'cash/bank',
            'current asset',
        ], true)
            || str_contains($name, 'bank')
            || str_contains($name, 'cash');
    }

    public function findLinkedBank(Account $account): ?Bank
    {
        if (!Schema::hasTable('banks')) {
            return null;
        }

        $accountName = strtolower(trim((string) $account->name));
        $companyId = (int) ($account->company_id ?? Auth::user()?->company_id ?? session('current_tenant_id') ?? 0);
        $branchId = trim((string) ($account->branch_id ?? session('active_branch_id', '')));
        $branchName = trim((string) ($account->branch_name ?? session('active_branch_name', '')));

        $banks = Bank::query()
            ->when($companyId > 0, fn ($query) => $query->where('company_id', $companyId))
            ->get();

        $filtered = $banks->filter(function (Bank $bank) use ($branchId, $branchName) {
            $bankBranchId = trim((string) ($bank->branch_id ?? ''));
            $bankBranchName = trim((string) ($bank->branch_name ?? $bank->branch ?? ''));

            if ($branchId !== '' && $bankBranchId !== '' && $branchId !== $bankBranchId) {
                return false;
            }

            if ($branchId === '' && $branchName !== '' && $bankBranchName !== '' && strcasecmp($branchName, $bankBranchName) !== 0) {
                return false;
            }

            return true;
        });

        return $filtered->first(function (Bank $bank) use ($accountName) {
            $bankName = strtolower(trim((string) ($bank->name ?? '')));
            return $bankName !== '' && (
                $bankName === $accountName
                || str_contains($bankName, $accountName)
                || str_contains($accountName, $bankName)
            );
        });
    }

    private function signedBalanceForAccountType(Account $account, float $debit, float $credit): float
    {
        return $account->isDebitAccount()
            ? ($debit - $credit)
            : ($credit - $debit);
    }

    private function paymentUsageCount(Account $account): int
    {
        if (!Schema::hasTable('payments')) {
            return 0;
        }

        $hasPaymentAccountId = Schema::hasColumn('payments', 'payment_account_id');
        $hasAccountId = Schema::hasColumn('payments', 'account_id');

        if (!$hasPaymentAccountId && !$hasAccountId) {
            return 0;
        }

        $query = Payment::query()->where(function ($sub) use ($account, $hasPaymentAccountId, $hasAccountId) {
            if ($hasPaymentAccountId) {
                $sub->where('payment_account_id', $account->id);
            }

            if ($hasAccountId) {
                if ($hasPaymentAccountId) {
                    $sub->orWhere('account_id', $account->id);
                } else {
                    $sub->where('account_id', $account->id);
                }
            }
        });

        return (int) $query->count();
    }

    private function statementSummary(?Bank $linkedBank): array
    {
        if (!$linkedBank || !Schema::hasTable('bank_statement_imports')) {
            return [
                'import_count' => 0,
                'line_count' => 0,
                'unmatched_count' => 0,
                'latest_closing_balance' => 0.0,
                'detail' => 'No bank statement imports found for this bank/payment channel.',
            ];
        }

        $imports = BankStatementImport::query()
            ->where('bank_id', $linkedBank->id)
            ->latest('id');

        $importCount = (int) (clone $imports)->count();
        $latestImport = (clone $imports)->first();

        $lineCount = 0;
        $unmatchedCount = 0;
        if (Schema::hasTable('bank_statement_lines')) {
            $lines = BankStatementLine::query()->where('bank_id', $linkedBank->id);
            $lineCount = (int) (clone $lines)->count();
            $unmatchedCount = (int) (clone $lines)
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '!=', 'matched');
                })
                ->count();
        }

        $latestClosingBalance = round((float) ($latestImport?->closing_balance ?? 0), 2);

        return [
            'import_count' => $importCount,
            'line_count' => $lineCount,
            'unmatched_count' => $unmatchedCount,
            'latest_closing_balance' => $latestClosingBalance,
            'detail' => $importCount > 0
                ? "{$importCount} import(s), {$lineCount} line(s), {$unmatchedCount} unmatched line(s)."
                : 'No bank statement imports found for this bank/payment channel.',
        ];
    }

    private function ensureOffsetAccount(int $companyId, int $userId, string $branchId, string $branchName): Account
    {
        $query = Account::withoutGlobalScopes()
            ->where('type', Account::TYPE_EQUITY)
            ->where('name', 'Bank Balance Clearing');

        if ($companyId > 0 && Schema::hasColumn('accounts', 'company_id')) {
            $query->where('company_id', $companyId);
        }

        $account = $query->first();
        if ($account) {
            return $account;
        }

        return Account::create([
            'code' => 'EQT-BBC-' . substr(md5($companyId . '|' . $branchId . '|' . $branchName . '|Bank Balance Clearing'), 0, 8),
            'name' => 'Bank Balance Clearing',
            'type' => Account::TYPE_EQUITY,
            'sub_type' => 'Reconciliation Reserve',
            'description' => 'System account used for audited bank balance zeroing journals.',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'company_id' => $companyId ?: null,
            'user_id' => $userId ?: null,
            'branch_id' => $branchId !== '' ? $branchId : null,
            'branch_name' => $branchName !== '' ? $branchName : null,
        ]);
    }

    private function journalLinesForBalance(Account $account, Account $offsetAccount, float $balance, float $amount): array
    {
        if ($account->isDebitAccount()) {
            return $balance > 0
                ? [
                    ['account_id' => $offsetAccount->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $account->id, 'debit' => 0, 'credit' => $amount],
                ]
                : [
                    ['account_id' => $account->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $offsetAccount->id, 'debit' => 0, 'credit' => $amount],
                ];
        }

        return $balance > 0
            ? [
                ['account_id' => $account->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $offsetAccount->id, 'debit' => 0, 'credit' => $amount],
            ]
            : [
                ['account_id' => $offsetAccount->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $account->id, 'debit' => 0, 'credit' => $amount],
            ];
    }

    private function recordAudit(Account $account, string $action, string $reason, array $oldDiagnostic, ?array $newDiagnostic, array $extra = []): void
    {
        ActivityLog::record(
            'BankBalanceManagement',
            $action,
            "Bank balance management action executed for {$account->name}. Reason: {$reason}",
            [
                'company_id' => $account->company_id ?? Auth::user()?->company_id ?? session('current_tenant_id'),
                'branch_id' => $account->branch_id ?? session('active_branch_id'),
                'branch_name' => $account->branch_name ?? session('active_branch_name'),
                'properties' => array_merge([
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'old_balance' => $oldDiagnostic['derived_ledger_balance'] ?? null,
                    'new_balance' => $newDiagnostic['derived_ledger_balance'] ?? null,
                    'stored_old_balance' => $oldDiagnostic['stored_current_balance'] ?? null,
                    'stored_new_balance' => $newDiagnostic['stored_current_balance'] ?? null,
                    'reason' => $reason,
                    'performed_by' => Auth::id(),
                    'performed_at' => now()->toDateTimeString(),
                ], $extra),
            ]
        );
    }
}

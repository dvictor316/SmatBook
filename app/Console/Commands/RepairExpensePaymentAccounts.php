<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Expense;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairExpensePaymentAccounts extends Command
{
    protected $signature = 'ledger:repair-expense-payment-accounts
        {company_id : Company to inspect}
        {--apply : Apply the changes listed by the preview}';

    protected $description = 'Find expense credits posted to a different asset account from their recorded Paid From account';

    public function handle(): int
    {
        $companyId = filter_var($this->argument('company_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$companyId) {
            $this->error('Provide a valid company ID.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $count = 0;
        Expense::withoutGlobalScopes()->where('company_id', $companyId)
            ->whereRaw("(LOWER(status) = 'paid' OR LOWER(payment_status) IN ('paid', 'completed'))")
            ->whereNotNull('payment_mode')
            ->eachById(function (Expense $expense) use ($companyId, $apply, &$count) {
                $change = DB::transaction(fn () => $this->inspect($companyId, $expense->id, $apply), 3);
                if ($change) {
                    $count++;
                    $this->line($change);
                }
            });

        $this->info($apply ? "Corrected {$count} expense credits." : "Found {$count} expense credits eligible for correction.");

        return self::SUCCESS;
    }

    private function inspect(int $companyId, int $expenseId, bool $apply): ?string
    {
        $expense = Expense::withoutGlobalScopes()->where('company_id', $companyId)
            ->lockForUpdate()->find($expenseId);
        if (!$expense || !(
            strtolower((string) $expense->status) === 'paid'
            || in_array(strtolower((string) $expense->payment_status), ['paid', 'completed'], true)
        )) {
            return null;
        }

        $branchId = trim((string) $expense->branch_id);
        $branchName = trim((string) $expense->branch_name);
        $name = strtolower(trim((string) $expense->payment_mode));
        if (!$expense->payment_account_id && in_array($name, [
            '', 'cash', 'bank', 'transfer', 'bank transfer', 'pos', 'card', 'cheque', 'check',
        ], true)) {
            return null;
        }

        $accounts = Account::withoutGlobalScope('tenant')->where('company_id', $companyId);
        $destinationQuery = (clone $accounts)->ofType(Account::TYPE_ASSET);
        if ($expense->payment_account_id) {
            $destinationQuery->whereKey($expense->payment_account_id);
        } else {
            $destinationQuery->whereRaw('LOWER(TRIM(name)) = ?', [$name])
                ->where('created_at', '<=', $expense->updated_at ?? $expense->created_at);
        }
        $destinations = $destinationQuery->get()
            ->filter(fn (Account $account) => $account->matchesAccountingBranch($branchId, $branchName));
        if ($destinations->count() !== 1) {
            return null;
        }
        $destination = $destinations->first();

        $entries = Transaction::withoutGlobalScope('tenant')->where('company_id', $companyId)
            ->where('related_type', Expense::class)->where('related_id', $expenseId)
            ->where('transaction_type', 'Expense')->lockForUpdate()->get();
        $amount = (float) $expense->amount;
        $credit = $entries->first(fn (Transaction $entry) => (float) $entry->credit === $amount && (float) $entry->debit === 0.0);
        $debit = $entries->first(fn (Transaction $entry) => (float) $entry->debit === $amount && (float) $entry->credit === 0.0);
        if ($amount <= 0 || $entries->count() !== 2 || !$credit || !$debit
            || !$credit->transaction_date || (int) $credit->account_id === (int) $destination->id
            || $entries->contains(fn (Transaction $entry) => (string) $entry->branch_id !== (string) $expense->branch_id
                || (string) $entry->branch_name !== (string) $expense->branch_name
                || !$entry->transaction_date?->isSameDay($credit->transaction_date))) {
            return null;
        }

        $origin = (clone $accounts)->ofType(Account::TYPE_ASSET)->find($credit->account_id);
        if (!$origin || !(clone $accounts)->ofType(Account::TYPE_EXPENSE)->whereKey($debit->account_id)->exists()) {
            return null;
        }

        $date = $credit->transaction_date->toDateString();
        $periodClosed = AccountingPeriod::withoutGlobalScope('tenant')->where('company_id', $companyId)
            ->where('status', 'closed')->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)
            ->exists();
        $matched = DB::table('bank_statement_lines')->whereIn('matched_transaction_id', $entries->modelKeys())->exists();
        if ($periodClosed || $matched) {
            $reason = $periodClosed ? 'closed accounting period' : 'bank statement match';
            $this->warn("Expense {$expenseId} needs review ({$reason}).");

            return null;
        }

        if ($apply) {
            $credit->account_id = $destination->id;
            $credit->save();
            $origin->updateBalance();
            $expense->payment_account_id = $destination->id;
            $expense->saveQuietly();

            Log::info('Expense payment account corrected', [
                'company_id' => $companyId,
                'expense_id' => $expenseId,
                'transaction_id' => $credit->id,
                'previous_account_id' => $origin->id,
                'payment_account_id' => $destination->id,
                'amount' => $expense->amount,
            ]);
        }

        return "Expense {$expenseId} ({$date}, {$expense->amount}) moves from {$origin->name} [{$origin->id}] to {$destination->name} [{$destination->id}].";
    }
}

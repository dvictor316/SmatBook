<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\BankBalanceManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RepairBankZeroJournal extends Command
{
    protected $signature = 'ledger:repair-bank-zero {company : Company ID} {reference : Exact BANKZERO journal reference} {--apply : Restore the missing clearing leg}';

    protected $description = 'Verify and restore one missing bank zeroing journal leg from its original audit record';

    public function handle(BankBalanceManagementService $service): int
    {
        $company = (int) $this->argument('company');
        $reference = (string) $this->argument('reference');
        if ($company <= 0 || !preg_match('/^BANKZERO-[1-9][0-9]*-[0-9]{14}$/', $reference)) {
            $this->error('Enter a positive company ID and the exact BANKZERO reference.');

            return self::FAILURE;
        }

        try {
            $message = DB::transaction(function () use ($company, $reference, $service) {
                $lines = Transaction::withoutGlobalScopes()
                    ->where('company_id', $company)->where('reference', $reference)
                    ->lockForUpdate()->get();
                if ($lines->count() === 2 && !$lines->contains(fn ($line) => $line->trashed())
                    && $lines->pluck('branch_id')->unique()->count() === 1
                    && $lines->pluck('branch_name')->unique()->count() === 1
                    && abs($lines->sum('debit') - $lines->sum('credit')) < 0.005) {
                    return 'Both posted journal legs are present and balanced.';
                }
                $bankId = (int) explode('-', $reference)[1];
                $bank = $lines->first(fn ($line) => (int) $line->account_id === $bankId && !$line->trashed());
                if (!$bank || !$bank->created_at || !(int) $bank->user_id) {
                    throw ValidationException::withMessages(['reference' => 'A surviving audited bank line is required.']);
                }
                $audit = $this->findAudit($company, $bank, $reference);

                return $service->recoverMissingOffset($lines, $company, $audit, (bool) $this->option('apply'));
            });
        } catch (ValidationException $error) {
            $this->error($error->validator->errors()->first());

            return self::FAILURE;
        }

        $this->info($message);

        return self::SUCCESS;
    }
    private function findAudit(int $company, Transaction $bank, string $reference): array
    {
        $prefix = 'Bank balance zeroing journal for ';
        if (!str_starts_with((string) $bank->description, $prefix)) {
            throw ValidationException::withMessages(['reference' => 'The surviving journal is missing the system bank-zero description.']);
        }

        $columns = ['branch_id', 'branch_name'];
        $hasProperties = Schema::hasColumn('activity_logs', 'properties');
        if ($hasProperties) {
            $columns[] = 'properties';
        }
        $entries = DB::table('activity_logs')->where('company_id', $company)->where('user_id', $bank->user_id)
            ->where('module', 'BankBalanceManagement')->where('action', 'bank_balance_zero_journal')
            ->where('description', 'Bank balance management action executed for '.substr($bank->description, strlen($prefix)))
            ->whereBetween('created_at', [$bank->created_at->copy()->subMinute(), $bank->created_at->copy()->addMinute()])
            ->get($columns)->map(function ($row) use ($bank) {
                $properties = json_decode($row->properties ?? '', true);
                $properties = is_string($properties) ? json_decode($properties, true) : $properties;

                return array_merge([
                    'account_id' => $bank->account_id,
                    'offset_account_name' => 'Bank Balance Clearing',
                    'old_balance' => (float) $bank->credit - (float) $bank->debit,
                ], is_array($properties) ? $properties : [], ['branch_id' => $row->branch_id, 'branch_name' => $row->branch_name]);
            })->filter(fn ($properties) => ($properties['reference'] ?? $reference) === $reference);

        if ($entries->count() !== 1) {
            throw ValidationException::withMessages(['reference' => 'A unique matching bank zeroing audit record is required.']);
        }

        return $entries->first();
    }

}

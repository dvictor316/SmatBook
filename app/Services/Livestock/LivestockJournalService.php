<?php

namespace App\Services\Livestock;

use App\Models\Account;
use App\Models\LivestockFarm;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LivestockJournalService
{
    public function post(Model $entry, LivestockFarm $farm, int $debitAccountId, int $creditAccountId): void
    {
        if ($entry->posted_at && !$entry->reversed_at) {
            throw ValidationException::withMessages(['post_to_ledger' => 'This farm record has already been posted.']);
        }
        if ($debitAccountId === $creditAccountId) {
            throw ValidationException::withMessages(['credit_account_id' => 'Debit and credit accounts must be different.']);
        }

        $accounts = Account::withoutGlobalScopes()->where('company_id', $entry->company_id)->whereIn('id', [$debitAccountId, $creditAccountId])->active()->get()->keyBy('id');
        if ($accounts->count() !== 2) {
            throw ValidationException::withMessages(['debit_account_id' => 'Select two active accounts belonging to this business.']);
        }

        $kind = str_contains($entry::class, 'Opex') ? 'OPEX' : 'REV';
        $date = $kind === 'OPEX' ? $entry->expense_date : $entry->revenue_date;
        $reference = "FARM-{$kind}-{$entry->id}";
        $description = ($kind === 'OPEX' ? 'Farm expense: ' : 'Farm revenue: ').($entry->description ?: ($entry->category ?? $entry->source));

        DB::transaction(function () use ($entry, $farm, $debitAccountId, $creditAccountId, $date, $reference, $description) {
            $this->line($entry, $farm, $debitAccountId, $date, $reference, $description, (float) $entry->amount, 0);
            $this->line($entry, $farm, $creditAccountId, $date, $reference, $description, 0, (float) $entry->amount);
            $entry->update(['debit_account_id' => $debitAccountId, 'credit_account_id' => $creditAccountId, 'journal_reference' => $reference, 'posted_at' => now(), 'reversed_at' => null]);
        });
    }

    public function reverse(Model $entry, LivestockFarm $farm): void
    {
        if (!$entry->posted_at || $entry->reversed_at) {
            throw ValidationException::withMessages(['journal' => 'Only an active posted journal can be reversed.']);
        }

        $originals = Transaction::withoutGlobalScopes()->where('company_id', $entry->company_id)
            ->where('related_type', $entry::class)->where('related_id', $entry->id)
            ->where('reference', $entry->journal_reference)->get();
        if ($originals->count() !== 2) {
            throw ValidationException::withMessages(['journal' => 'The original balanced journal could not be found.']);
        }

        DB::transaction(function () use ($entry, $farm, $originals) {
            foreach ($originals as $line) {
                $this->line($entry, $farm, (int) $line->account_id, now()->toDateString(), 'REV-'.$entry->journal_reference, 'Reversal: '.$line->description, (float) $line->credit, (float) $line->debit);
            }
            $entry->update(['reversed_at' => now()]);
        });
    }

    private function line(Model $entry, LivestockFarm $farm, int $accountId, $date, string $reference, string $description, float $debit, float $credit): void
    {
        Transaction::create([
            'account_id' => $accountId,
            'company_id' => $entry->company_id,
            'branch_id' => $farm->branch_id ?: session('active_branch_id'),
            'branch_name' => $farm->branch_name ?: session('active_branch_name'),
            'transaction_date' => $date,
            'reference' => $reference,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'transaction_type' => Transaction::TYPE_JOURNAL,
            'related_id' => $entry->id,
            'related_type' => $entry::class,
            'user_id' => auth()->id(),
        ]);
    }
}

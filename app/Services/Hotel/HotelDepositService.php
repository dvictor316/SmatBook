<?php

namespace App\Services\Hotel;

use App\Models\Account;
use App\Models\FolioItem;
use App\Models\GuestFolio;
use App\Models\HotelDepositTransaction;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Support\HotelPropertyContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HotelDepositService
{
    public function receive(Reservation $reservation, float $amount, string $method = 'cash', ?int $accountId = null, ?string $reference = null, ?string $notes = null): HotelDepositTransaction
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['deposit_amount' => 'Deposit amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($reservation, $amount, $method, $accountId, $reference, $notes) {
            $cash = $this->cashAccount($reservation->company_id, $accountId, $method);
            $advance = $this->systemAccount($reservation->company_id, 'AUTO-LIB-HOTEL-ADV', 'Hotel Guest Advances', Account::TYPE_LIABILITY, 'Current Liability');
            $deposit = HotelDepositTransaction::create([
                'company_id' => $reservation->company_id, 'property_id' => $reservation->property_id, 'reservation_id' => $reservation->id,
                'transaction_date' => now()->toDateString(), 'transaction_type' => 'receipt', 'payment_method' => $method,
                'account_id' => $cash->id, 'amount' => round($amount, 2), 'reference' => $reference, 'status' => 'posted', 'notes' => $notes, 'recorded_by' => auth()->id(),
            ]);
            $this->postLines($deposit, $cash, $advance, $amount, 'Reservation deposit received');
            $reservation->increment('deposit_received', round($amount, 2));
            $reservation->update(['balance' => max(0, (float) $reservation->total - (float) $reservation->fresh()->deposit_received)]);

            return $deposit;
        });
    }

    public function refund(Reservation $reservation, float $amount, ?int $accountId = null, ?string $reference = null, ?string $notes = null): HotelDepositTransaction
    {
        $available = (float) $reservation->deposit_received;
        if ($amount <= 0 || $amount > $available) {
            throw ValidationException::withMessages(['deposit_amount' => 'Refund must be greater than zero and cannot exceed the unapplied reservation deposit.']);
        }

        return DB::transaction(function () use ($reservation, $amount, $accountId, $reference, $notes) {
            $cash = $this->cashAccount($reservation->company_id, $accountId, 'cash');
            $advance = $this->systemAccount($reservation->company_id, 'AUTO-LIB-HOTEL-ADV', 'Hotel Guest Advances', Account::TYPE_LIABILITY, 'Current Liability');
            $refund = HotelDepositTransaction::create([
                'company_id' => $reservation->company_id, 'property_id' => $reservation->property_id, 'reservation_id' => $reservation->id,
                'transaction_date' => now()->toDateString(), 'transaction_type' => 'refund', 'payment_method' => 'refund',
                'account_id' => $cash->id, 'amount' => round($amount, 2), 'reference' => $reference, 'status' => 'posted', 'notes' => $notes, 'recorded_by' => auth()->id(),
            ]);
            $this->postLines($refund, $advance, $cash, $amount, 'Reservation deposit refunded');
            $reservation->decrement('deposit_received', round($amount, 2));
            $reservation->update(['balance' => max(0, (float) $reservation->total - (float) $reservation->fresh()->deposit_received)]);

            return $refund;
        });
    }

    public function postApplication(FolioItem $item, GuestFolio $folio): void
    {
        if (Transaction::withoutGlobalScopes()->where('related_type', FolioItem::class)->where('related_id', $item->id)->exists()) {
            return;
        }
        $advance = $this->systemAccount($folio->company_id, 'AUTO-LIB-HOTEL-ADV', 'Hotel Guest Advances', Account::TYPE_LIABILITY, 'Current Liability');
        $receivable = $this->systemAccount($folio->company_id, 'AUTO-AST-AR', 'Accounts Receivable', Account::TYPE_ASSET, 'Accounts Receivable');
        $this->postLines($item, $advance, $receivable, (float) $item->amount, 'Hotel deposit applied to folio');
    }

    private function cashAccount(int $companyId, ?int $accountId, string $method): Account
    {
        if ($accountId) {
            $account = Account::withoutGlobalScopes()->where('company_id', $companyId)->whereKey($accountId)->where('type', Account::TYPE_ASSET)->first();
            if ($account) {
                return $account;
            }
        }
        $code = 'AUTO-AST-'.strtoupper(substr($method, 0, 8));
        return $this->systemAccount($companyId, $code, ucfirst($method).' Clearing', Account::TYPE_ASSET, 'Cash & Bank');
    }

    private function systemAccount(int $companyId, string $code, string $name, string $type, string $subType): Account
    {
        return Account::withoutGlobalScopes()->firstOrCreate(['company_id' => $companyId, 'code' => $code], [
            'name' => $name, 'type' => $type, 'sub_type' => $subType, 'opening_balance' => 0, 'current_balance' => 0,
            'is_active' => true, 'user_id' => auth()->id(), 'branch_id' => HotelPropertyContext::activeBranchId(), 'branch_name' => HotelPropertyContext::activeBranchName(),
        ]);
    }

    private function postLines(object $related, Account $debit, Account $credit, float $amount, string $description): void
    {
        $reference = 'HDEP-'.($related->id ?? now()->timestamp);
        $base = ['company_id' => $debit->company_id, 'branch_id' => HotelPropertyContext::activeBranchId(), 'branch_name' => HotelPropertyContext::activeBranchName(), 'transaction_date' => now()->toDateString(), 'reference' => $reference, 'description' => $description, 'transaction_type' => Transaction::TYPE_JOURNAL, 'related_id' => $related->id, 'related_type' => $related::class, 'user_id' => auth()->id()];
        Transaction::create([...$base, 'account_id' => $debit->id, 'debit' => round($amount, 2), 'credit' => 0]);
        Transaction::create([...$base, 'account_id' => $credit->id, 'debit' => 0, 'credit' => round($amount, 2)]);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Expense;
use App\Support\LedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillLedgerFromOperations extends Command
{
    protected $signature = 'ledger:backfill-operations {--chunk=200}';
    protected $description = 'Backfill transactions ledger from existing sales, purchases, and returns.';

    public function handle(): int
    {
        if (!Schema::hasTable('accounts') || !Schema::hasTable('transactions')) {
            $this->error('Missing accounting tables: accounts/transactions.');
            return self::FAILURE;
        }

        $chunk = max(50, (int) $this->option('chunk'));
        $salesCount = 0;
        $purchaseCount = 0;
        $purchaseReturnCount = 0;
        $creditNoteCount = 0;
        $paymentCount = 0;
        $expenseCount = 0;

        Sale::query()->orderBy('id')->chunkById($chunk, function ($sales) use (&$salesCount) {
            foreach ($sales as $sale) {
                LedgerService::postSale($sale);
                $salesCount++;
            }
        });

        if (class_exists(Purchase::class) && Schema::hasTable('purchases')) {
            Purchase::query()->orderBy('id')->chunkById($chunk, function ($purchases) use (&$purchaseCount) {
                foreach ($purchases as $purchase) {
                    LedgerService::postPurchase($purchase);
                    $purchaseCount++;
                }
            });
        }

        if (class_exists(PurchaseReturn::class) && Schema::hasTable('purchase_returns')) {
            PurchaseReturn::query()->orderBy('id')->chunkById($chunk, function ($returns) use (&$purchaseReturnCount) {
                foreach ($returns as $return) {
                    LedgerService::postPurchaseReturn(
                        relatedId: (int) $return->id,
                        amount: (float) ($return->amount ?? 0),
                        reference: (string) ($return->return_no ?? ('RET-' . $return->id)),
                        date: optional($return->created_at)->toDateString(),
                        userId: auth()->id(),
                        relatedType: PurchaseReturn::class
                    );
                    $purchaseReturnCount++;
                }
            });
        }

        if (Schema::hasTable('credit_notes')) {
            DB::table('credit_notes')
                ->select('id', 'credit_note_no', 'credit_date', 'total_amount')
                ->orderBy('id')
                ->chunk($chunk, function ($notes) use (&$creditNoteCount) {
                    foreach ($notes as $note) {
                        LedgerService::postSalesReturn(
                            relatedId: (int) $note->id,
                            amount: (float) ($note->total_amount ?? 0),
                            reference: (string) ($note->credit_note_no ?? ('CN-' . $note->id)),
                            date: $note->credit_date ?: null,
                            userId: null,
                            relatedType: 'credit_note'
                        );
                        $creditNoteCount++;
                    }
                });
        }

        if (class_exists(Payment::class) && Schema::hasTable('payments')) {
            Payment::query()->with('sale')->orderBy('id')->chunkById($chunk, function ($payments) use (&$paymentCount) {
                foreach ($payments as $payment) {
                    if ($payment->sale) {
                        LedgerService::postSalePayment($payment->sale, $payment, $payment->reference ?: $payment->payment_id);
                    } else {
                        LedgerService::postStandalonePayment($payment);
                    }
                    $paymentCount++;
                }
            });
        }

        if (class_exists(Expense::class) && Schema::hasTable('expenses')) {
            Expense::query()->orderBy('id')->chunkById($chunk, function ($expenses) use (&$expenseCount) {
                foreach ($expenses as $expense) {
                    LedgerService::postExpense($expense);
                    $expenseCount++;
                }
            });
        }

        $this->info("Backfill complete. Sales: {$salesCount}, Purchases: {$purchaseCount}, Purchase Returns: {$purchaseReturnCount}, Credit Notes: {$creditNoteCount}, Payments: {$paymentCount}, Expenses: {$expenseCount}");
        return self::SUCCESS;
    }
}

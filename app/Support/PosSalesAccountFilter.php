<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class PosSalesAccountFilter
{
    // POS partial-sale receipts use Pending for money already received.
    private const IGNORED_STATUSES = ['failed', 'error', 'cancelled', 'canceled', 'refunded', 'pending approval', 'rejected', 'void'];

    private const CHANNELS = [
        'payment_account_id' => 'cash',
        'transfer_account_id' => 'transfer',
        'card_account_id' => 'card',
    ];

    public static function apply(Builder $query, int $accountId): void
    {
        $hasPayments = Schema::hasTable('payments');
        $query->where(function (Builder $matches) use ($accountId, $hasPayments) {
            if ($hasPayments) {
                $matches->whereHas('payments', function (Builder $payments) use ($accountId) {
                    self::appliedPayments($payments);
                    $payments->where('amount', '>', 0)->where(function (Builder $account) use ($accountId) {
                        if (Schema::hasColumn('payments', 'payment_account_id')) {
                            $account->where('payment_account_id', $accountId);
                            if (Schema::hasColumn('payments', 'account_id')) {
                                $account->orWhere(function (Builder $legacy) use ($accountId) {
                                    $legacy->whereNull('payment_account_id')->where('account_id', $accountId);
                                });
                            }
                        } elseif (Schema::hasColumn('payments', 'account_id')) {
                            $account->where('account_id', $accountId);
                        } else {
                            $account->whereRaw('1 = 0');
                        }
                    });
                });
            }
            $snapshot = function (Builder $legacy) use ($accountId, $hasPayments) {
                // Payment rows are authoritative whenever they exist for a sale.
                if ($hasPayments) {
                    $legacy->whereDoesntHave('payments');
                }
                if (! Schema::hasColumn('sales', 'payment_details')) {
                    $legacy->whereRaw('1 = 0');

                    return;
                }
                $legacy->where(function (Builder $channels) use ($accountId) {
                    foreach (self::CHANNELS as $key => $channel) {
                        $channels->orWhere(function (Builder $part) use ($key, $channel, $accountId) {
                            $part->whereIn('sales.payment_details->'.$key, [$accountId, (string) $accountId])
                                ->where(function (Builder $amount) use ($channel) {
                                    $amount->where('sales.payment_details->split->'.$channel, '>', 0)
                                        ->orWhere(function (Builder $direct) use ($channel) {
                                            $methods = match ($channel) {
                                                'transfer' => ['transfer', 'bank_transfer', 'bank transfer'],
                                                'card' => ['card', 'pos', 'credit_card', 'pos/card'],
                                                default => ['cash', 'transfer', 'bank_transfer', 'bank transfer', 'card', 'pos', 'credit_card', 'pos/card'],
                                            };
                                            $direct->whereRaw('LOWER(sales.payment_method) IN ('.implode(',', array_fill(0, count($methods), '?')).')', $methods)
                                                ->where('sales.amount_paid', '>', 0);

                                            foreach (['cash', 'transfer', 'card'] as $splitChannel) {
                                                $direct->where(function (Builder $empty) use ($splitChannel) {
                                                    $empty->whereNull('sales.payment_details->split->'.$splitChannel)
                                                        ->orWhere('sales.payment_details->split->'.$splitChannel, '<=', 0);
                                                });
                                            }
                                        });
                                });
                        });
                    }
                });
            };
            $hasPayments ? $matches->orWhere($snapshot) : $matches->where($snapshot);
        });
    }

    private static function appliedPayments(Builder $query): void
    {
        if (Schema::hasColumn('payments', 'status')) {
            $query->whereRaw("LOWER(TRIM(COALESCE(status, ''))) NOT IN (".implode(',', array_fill(0, count(self::IGNORED_STATUSES), '?')).')', self::IGNORED_STATUSES);
        }
    }

    public static function amount(Sale $sale, int $accountId): float
    {
        if ($sale->relationLoaded('payments') && $sale->payments->isNotEmpty()) {
            return round((float) $sale->payments->filter(fn (Payment $payment) => (int) ($payment->payment_account_id ?? $payment->account_id) === $accountId
                && ! in_array(strtolower(trim((string) $payment->status)), self::IGNORED_STATUSES, true)
                && (float) $payment->amount > 0
            )->sum('amount'), 2);
        }
        $details = is_array($sale->payment_details) ? $sale->payment_details : [];
        $split = is_array($details['split'] ?? null) ? $details['split'] : [];
        $amount = 0.0;
        $hasSplit = collect(['cash', 'transfer', 'card'])->contains(fn ($key) => (float) ($split[$key] ?? 0) > 0);
        foreach (self::CHANNELS as $key => $channel) {
            if ((int) ($details[$key] ?? 0) !== $accountId) {
                continue;
            }
            if ($hasSplit) {
                $amount += max(0, (float) ($split[$channel] ?? 0));
            } elseif (self::directChannel($sale) === $channel || $key === 'payment_account_id') {
                // The generic account key was used by older single-mode sales.
                return round(max(0, (float) ($sale->amount_paid ?? $sale->paid ?? 0)), 2);
            }
        }

        return round($amount, 2);
    }

    private static function directChannel(Sale $sale): string
    {
        return match (strtolower((string) $sale->payment_method)) {
            'transfer', 'bank_transfer', 'bank transfer' => 'transfer',
            'card', 'pos', 'credit_card', 'pos/card' => 'card',
            default => 'cash',
        };
    }
}

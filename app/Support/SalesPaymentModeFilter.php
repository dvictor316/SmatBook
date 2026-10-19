<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesPaymentModeFilter
{
    public static function apply(EloquentBuilder|QueryBuilder $query, string $paymentMode, string $table = 'sales'): void
    {
        $paymentMode = strtolower(trim($paymentMode));
        $methodColumn = "{$table}.payment_method";
        $detailsColumn = "{$table}.payment_details";

        $paidColumns = array_values(array_filter(['amount_paid', 'paid'], fn ($column) => Schema::hasColumn($table, $column)));
        if ($paymentMode === 'cash') {
            // A credit invoice has no cash receipt, even if an old row says Cash.
            $query->where(function ($paid) use ($paidColumns, $table) {
                if ($paidColumns === []) {
                    $paid->whereRaw('1 = 0');
                }
                foreach ($paidColumns as $column) {
                    $paid->orWhere($table.'.'.$column, '>', 0);
                }
            });
        }
        if ($paymentMode === 'credit') {
            $query->where($table.'.total', '>', 0)
                ->whereNotIn(DB::raw("LOWER(COALESCE({$methodColumn}, ''))"), ['charge_to_room', 'chargetoroom', 'charge-room']);
            foreach ($paidColumns as $column) {
                $query->where(function ($unpaid) use ($table, $column) {
                    $unpaid->whereNull($table.'.'.$column)->orWhere($table.'.'.$column, '<=', 0);
                });
            }
            return;
        }

        $query->where(function ($builder) use ($paymentMode, $methodColumn, $detailsColumn, $table) {
            $splitChannel = function (string $channel) use ($builder, $methodColumn, $detailsColumn, $table): void {
                $builder->orWhere(function ($splitQuery) use ($channel, $methodColumn, $detailsColumn, $table) {
                    $splitQuery->whereRaw("LOWER({$methodColumn}) = ?", ['split']);
                    if (Schema::hasColumn($table, 'payment_details')) {
                        $splitQuery->where("{$detailsColumn}->split->{$channel}", '>', 0);
                    } else {
                        $splitQuery->whereRaw('1 = 0');
                    }
                });
            };

            if ($paymentMode === 'cash') {
                $builder->whereRaw("LOWER({$methodColumn}) = ?", ['cash']);
                $splitChannel('cash');
            } elseif (in_array($paymentMode, ['transfer', 'bank_transfer'], true)) {
                $builder->whereIn(DB::raw("LOWER({$methodColumn})"), ['transfer', 'bank_transfer']);
                $splitChannel('transfer');
            } elseif (in_array($paymentMode, ['card', 'pos', 'credit_card'], true)) {
                $builder->whereIn(DB::raw("LOWER({$methodColumn})"), ['card', 'pos', 'credit_card']);
                $splitChannel('card');
            } elseif ($paymentMode === 'wallet') {
                if (Schema::hasColumn($table, 'payment_details')) {
                    $builder->where(function ($walletQuery) use ($detailsColumn) {
                        $walletQuery->where("{$detailsColumn}->wallet_applied", '>', 0)
                            ->orWhere("{$detailsColumn}->wallet_requested", '>', 0);
                    });
                } else {
                    $builder->whereRaw('1 = 0');
                }
            } elseif (in_array($paymentMode, ['charge_to_room', 'chargetoroom', 'charge-room'], true)) {
                $builder->whereIn(DB::raw("LOWER({$methodColumn})"), ['charge_to_room', 'chargetoroom', 'charge-room']);
            } else {
                $builder->whereRaw("LOWER({$methodColumn}) = ?", [$paymentMode]);
            }
        });
    }
}

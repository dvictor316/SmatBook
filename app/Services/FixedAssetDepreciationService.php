<?php

namespace App\Services;

use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FixedAssetDepreciationService
{
    private const FREQUENCY_MONTHS = [
        'monthly' => 1,
        'quarterly' => 3,
        'yearly' => 12,
    ];

    public function runDueForAsset(FixedAsset $asset, ?string $asOfDate = null, ?int $userId = null): array
    {
        $asOf = Carbon::parse($asOfDate ?: now()->toDateString())->startOfDay();

        return DB::transaction(function () use ($asset, $asOf, $userId) {
            $asset->refresh();

            if (($asset->status ?? 'active') !== 'active') {
                return ['posted' => 0, 'amount' => 0.0, 'message' => 'Only active assets can be depreciated.'];
            }

            $posted = 0;
            $totalAmount = 0.0;
            $guard = 0;

            while ($guard < 240 && $this->isDue($asset, $asOf)) {
                $result = $this->postOneScheduledPeriod($asset, $asOf, $userId);
                if (($result['posted'] ?? false) !== true) {
                    return [
                        'posted' => $posted,
                        'amount' => round($totalAmount, 2),
                        'message' => $result['message'] ?? 'No depreciation was due.',
                    ];
                }

                $posted++;
                $totalAmount += (float) $result['amount'];
                $asset->refresh();
                $guard++;
            }

            return [
                'posted' => $posted,
                'amount' => round($totalAmount, 2),
                'message' => $posted > 0 ? 'Depreciation posted successfully.' : 'No depreciation is due yet.',
            ];
        });
    }

    public function runDueForAll(?string $asOfDate = null): array
    {
        $asOf = Carbon::parse($asOfDate ?: now()->toDateString())->toDateString();
        $summary = ['assets' => 0, 'periods' => 0, 'amount' => 0.0];

        FixedAsset::query()
            ->where('status', 'active')
            ->where(function ($query) use ($asOf) {
                $query->whereNull('next_depreciation_on')
                    ->orWhereDate('next_depreciation_on', '<=', $asOf);
            })
            ->orderBy('id')
            ->chunkById(100, function ($assets) use (&$summary, $asOf) {
                foreach ($assets as $asset) {
                    $result = $this->runDueForAsset($asset, $asOf);
                    if ((int) $result['posted'] > 0) {
                        $summary['assets']++;
                        $summary['periods'] += (int) $result['posted'];
                        $summary['amount'] += (float) $result['amount'];
                    }
                }
            });

        $summary['amount'] = round($summary['amount'], 2);

        return $summary;
    }

    public function previewAmount(FixedAsset $asset): float
    {
        $depreciableBase = $this->depreciableBase($asset);
        $remaining = $this->remainingDepreciableAmount($asset);
        if ($remaining <= 0.009) {
            return 0.0;
        }

        $monthlyAmount = round($depreciableBase / max(1, (int) ($asset->useful_life_months ?? 1)), 2);
        $amount = $monthlyAmount * $this->frequencyMonths($asset);

        return round(min($remaining, $amount), 2);
    }

    public function nextDueDate(FixedAsset $asset): string
    {
        if ($asset->next_depreciation_on) {
            return Carbon::parse($asset->next_depreciation_on)->toDateString();
        }

        $baseDate = $asset->last_depreciated_on ?: $asset->acquired_on ?: now();

        return Carbon::parse($baseDate)
            ->addMonthsNoOverflow($this->frequencyMonths($asset))
            ->toDateString();
    }

    private function postOneScheduledPeriod(FixedAsset $asset, Carbon $asOf, ?int $userId): array
    {
        $cost = (float) ($asset->cost ?? 0);
        $salvage = (float) ($asset->salvage_value ?? 0);
        $remaining = $this->remainingDepreciableAmount($asset);

        if ($remaining <= 0.009) {
            $asset->status = 'fully_depreciated';
            $asset->book_value = max($salvage, round($cost - (float) $asset->accumulated_depreciation, 2));
            $asset->save();

            return ['posted' => false, 'message' => 'This asset is already fully depreciated.'];
        }

        $frequencyMonths = $this->frequencyMonths($asset);
        $periodEnd = Carbon::parse($this->nextDueDate($asset))->startOfDay();
        if ($periodEnd->greaterThan($asOf)) {
            return ['posted' => false, 'message' => 'No depreciation is due yet.'];
        }

        $periodStart = $asset->last_depreciated_on
            ? Carbon::parse($asset->last_depreciated_on)->addDay()
            : Carbon::parse($asset->acquired_on ?: $periodEnd->copy()->subMonthsNoOverflow($frequencyMonths));

        if (FixedAssetDepreciation::where('fixed_asset_id', $asset->id)->whereDate('period_end_on', $periodEnd->toDateString())->exists()) {
            $asset->next_depreciation_on = $periodEnd->copy()->addMonthsNoOverflow($frequencyMonths)->toDateString();
            $asset->save();

            return ['posted' => true, 'amount' => 0.0];
        }

        $amount = $this->previewAmount($asset);
        if ($amount <= 0.009) {
            return ['posted' => false, 'message' => 'This asset is already fully depreciated.'];
        }

        $runDate = $periodEnd->toDateString();
        $reference = 'FADP-' . now()->format('Ymd-His') . '-' . $asset->id . '-' . $periodEnd->format('Ym');
        $description = 'Depreciation for fixed asset ' . ($asset->asset_code ?: $asset->name) . ' through ' . $periodEnd->format('M Y');

        $this->postTransaction($asset->expense_account_id, $runDate, $reference, $description, $amount, 0, $asset, $userId);
        $this->postTransaction($asset->depreciation_account_id, $runDate, $reference, $description, 0, $amount, $asset, $userId);

        FixedAssetDepreciation::create([
            'company_id' => $asset->company_id,
            'branch_id' => $asset->branch_id,
            'branch_name' => $asset->branch_name,
            'fixed_asset_id' => $asset->id,
            'created_by' => $userId,
            'run_date' => $runDate,
            'period_start_on' => $periodStart->toDateString(),
            'period_end_on' => $periodEnd->toDateString(),
            'period_label' => $periodEnd->format('M Y'),
            'depreciation_frequency' => $this->frequency($asset),
            'amount' => $amount,
            'reference_no' => $reference,
            'notes' => $description,
        ]);

        $asset->accumulated_depreciation = round((float) $asset->accumulated_depreciation + $amount, 2);
        $asset->book_value = max($salvage, round($cost - (float) $asset->accumulated_depreciation, 2));
        $asset->last_depreciated_on = $runDate;
        $asset->next_depreciation_on = $periodEnd->copy()->addMonthsNoOverflow($frequencyMonths)->toDateString();
        if ($asset->book_value <= $salvage + 0.009) {
            $asset->status = 'fully_depreciated';
            $asset->next_depreciation_on = null;
        }
        $asset->save();

        return ['posted' => true, 'amount' => $amount];
    }

    private function postTransaction(int $accountId, string $date, string $reference, string $description, float $debit, float $credit, FixedAsset $asset, ?int $userId): void
    {
        Transaction::create([
            'account_id' => $accountId,
            'company_id' => $asset->company_id,
            'branch_id' => $asset->branch_id,
            'branch_name' => $asset->branch_name,
            'transaction_date' => $date,
            'reference' => $reference,
            'description' => $description,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'balance' => 0,
            'transaction_type' => Transaction::TYPE_JOURNAL,
            'related_id' => $asset->id,
            'related_type' => FixedAsset::class,
            'user_id' => $userId,
        ]);
    }

    private function isDue(FixedAsset $asset, Carbon $asOf): bool
    {
        return ($asset->status ?? 'active') === 'active'
            && Carbon::parse($this->nextDueDate($asset))->startOfDay()->lessThanOrEqualTo($asOf);
    }

    private function depreciableBase(FixedAsset $asset): float
    {
        return max(0, (float) ($asset->cost ?? 0) - (float) ($asset->salvage_value ?? 0));
    }

    private function remainingDepreciableAmount(FixedAsset $asset): float
    {
        return max(0, $this->depreciableBase($asset) - (float) ($asset->accumulated_depreciation ?? 0));
    }

    private function frequency(FixedAsset $asset): string
    {
        $frequency = strtolower((string) ($asset->depreciation_frequency ?? 'monthly'));

        return array_key_exists($frequency, self::FREQUENCY_MONTHS) ? $frequency : 'monthly';
    }

    private function frequencyMonths(FixedAsset $asset): int
    {
        return self::FREQUENCY_MONTHS[$this->frequency($asset)];
    }
}

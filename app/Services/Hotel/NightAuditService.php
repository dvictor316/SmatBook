<?php

namespace App\Services\Hotel;

use App\Models\HotelNightAudit;
use App\Models\HotelNightlyCharge;
use App\Models\Stay;
use App\Support\LedgerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NightAuditService
{
    public function __construct(
        private readonly HotelFolioService $folioService
    ) {
    }

    public function run(int $companyId, int $propertyId, string $auditDate, int $userId, bool $force = false): HotelNightAudit
    {
        $auditDay = Carbon::parse($auditDate)->toDateString();

        return DB::transaction(function () use ($companyId, $propertyId, $auditDay, $userId, $force) {
            $audit = HotelNightAudit::query()->firstOrNew([
                'company_id' => $companyId,
                'property_id' => $propertyId,
                'audit_date' => $auditDay,
            ]);

            if ($audit->exists && !$force && in_array((string) $audit->status, ['completed', 'closed'], true)) {
                return $audit;
            }

            $windowStart = Carbon::parse($auditDay)->startOfDay();
            $windowEnd = Carbon::parse($auditDay)->endOfDay();

            $stays = Stay::query()
                ->where('company_id', $companyId)
                ->where('property_id', $propertyId)
                ->where('status', 'checked_in')
                ->where('checkin_at', '<=', $windowEnd)
                ->where(function ($query) use ($windowStart) {
                    $query->whereNull('actual_checkout_at')
                        ->orWhere('actual_checkout_at', '>', $windowStart);
                })
                ->lockForUpdate()
                ->get();

            $posted = 0;
            $skipped = 0;
            $total = 0.0;

            foreach ($stays as $stay) {
                $amount = round(max(0, (float) ($stay->agreed_rate ?? 0)), 2);

                $charge = HotelNightlyCharge::query()->firstOrNew([
                    'company_id' => $companyId,
                    'property_id' => $propertyId,
                    'stay_id' => $stay->id,
                    'charge_date' => $auditDay,
                ]);

                if ($charge->exists && in_array((string) $charge->status, ['posted', 'reversed'], true) && !$force) {
                    $skipped++;
                    continue;
                }

                if ($amount <= 0) {
                    $charge->fill([
                        'folio_id' => $charge->folio_id ?: 0,
                        'room_id' => $stay->room_id,
                        'amount' => 0,
                        'status' => 'skipped',
                        'night_audit_id' => $audit->id,
                        'posted_by' => $userId,
                        'posted_at' => now(),
                        'note' => 'No agreed rate on stay.',
                    ])->save();

                    $skipped++;
                    continue;
                }

                $folio = $this->folioService->getOrCreateOpenFolioForStay($stay);

                $folioItem = $this->folioService->postCharge($folio, [
                    'description' => 'Room Night Charge - ' . $auditDay,
                    'amount' => $amount,
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'type' => 'room_night',
                    'service_code' => 'ROOM_NIGHT',
                    'service_date' => $auditDay,
                    'source_type' => HotelNightAudit::class,
                    'source_id' => $audit->id,
                    'posting_key' => 'night:' . $stay->id . ':' . $auditDay,
                    'posted_by' => $userId,
                ]);

                LedgerService::postHotelFolioCharge($folioItem, $folio, $stay->branch_id ?? null, $stay->branch_name ?? null);

                $charge->fill([
                    'folio_id' => $folio->id,
                    'room_id' => $stay->room_id,
                    'amount' => $amount,
                    'status' => 'posted',
                    'folio_item_id' => $folioItem->id,
                    'night_audit_id' => $audit->id,
                    'posted_by' => $userId,
                    'posted_at' => now(),
                    'note' => null,
                ])->save();

                $posted++;
                $total += $amount;
            }

            $audit->fill([
                'status' => 'completed',
                'stays_scanned' => $stays->count(),
                'charges_posted' => $posted,
                'charges_skipped' => $skipped,
                'total_amount' => round($total, 2),
                'run_by' => $userId,
                'run_at' => now(),
                'meta' => [
                    'force' => $force,
                ],
            ])->save();

            return $audit->fresh();
        });
    }

    public function reopen(HotelNightAudit $audit, int $userId, ?string $reason = null): HotelNightAudit
    {
        $audit->update([
            'status' => 'reopened',
            'reopened_by' => $userId,
            'reopened_at' => now(),
            'reopen_reason' => $reason,
        ]);

        return $audit->fresh();
    }
}

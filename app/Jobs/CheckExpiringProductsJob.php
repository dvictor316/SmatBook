<?php

namespace App\Jobs;

use App\Models\ProductLot;
use App\Models\User;
use App\Notifications\ExpiringProductsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;

class CheckExpiringProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Days ahead to look for expiring products (5 months ≈ 150 days). */
    private const THRESHOLD_DAYS = 150;

    public function handle(): void
    {
        if (!Schema::hasTable('product_lots')) {
            return;
        }

        $thresholdDate = now()->addDays(self::THRESHOLD_DAYS)->toDateString();
        $today         = now()->toDateString();

        // Fetch lots expiring within the threshold window with stock remaining
        $expiringLots = ProductLot::query()
            ->with('product:id,name')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $thresholdDate)
            ->where('quantity_available', '>', 0)
            ->orderBy('expiry_date', 'asc')
            ->get();

        if ($expiringLots->isEmpty()) {
            return;
        }

        // Notify admin/owner users per company
        $lotsByCompany = $expiringLots->groupBy('company_id');

        foreach ($lotsByCompany as $companyId => $lots) {
            if (!$companyId) {
                continue;
            }

            $admins = User::query()
                ->where('company_id', (int) $companyId)
                ->where(function ($q) {
                    // Match both the normalised `role` string column and the
                    // role_id relationship's role name via a raw OR.
                    $q->whereIn('role', ['super_admin', 'administrator', 'admin', 'owner'])
                      ->orWhereHas('role', fn ($r) => $r->whereIn('name', ['super_admin', 'administrator', 'admin', 'owner']));
                })
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new ExpiringProductsNotification($lots, self::THRESHOLD_DAYS));
            }
        }
    }
}

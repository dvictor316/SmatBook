<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ExpiringProductsNotification extends Notification
{
    use Queueable;

    /**
     * @param  \Illuminate\Support\Collection  $lots  ProductLot instances with 'product' relation loaded
     * @param  int  $thresholdDays  Number of days used for the expiry window
     */
    public function __construct(
        private readonly Collection $lots,
        private readonly int $thresholdDays = 150
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $count     = $this->lots->count();
        $firstFew  = $this->lots->take(5)->map(fn ($lot) => [
            'product_name' => $lot->product?->name ?? ('Product #' . $lot->product_id),
            'expiry_date'  => $lot->expiry_date?->toDateString(),
        ])->values()->all();

        return [
            'type'           => 'expiring_products',
            'title'          => "⚠️ {$count} Product Lot" . ($count === 1 ? '' : 's') . " Expiring Within {$this->thresholdDays} Days",
            'message'        => "You have {$count} product lot(s) expiring within {$this->thresholdDays} days. Please review your inventory.",
            'count'          => $count,
            'threshold_days' => $this->thresholdDays,
            'items'          => $firstFew,
            'url'            => '/inventory/lots',
        ];
    }
}

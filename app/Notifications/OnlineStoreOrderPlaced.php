<?php

namespace App\Notifications;

use App\Models\OnlineStoreOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OnlineStoreOrderPlaced extends Notification
{
    use Queueable;

    public function __construct(
        private readonly OnlineStoreOrder $order,
        private readonly string $storeName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'online_store_order',
            'title' => 'New online order',
            'message' => "{$this->order->order_number} was placed at {$this->storeName}.",
            'user_id' => $notifiable->getKey(),
            'user_name' => $this->storeName,
            'user_avatar' => '/assets/img/profiles/avatar-01.jpg',
            'action_text' => 'received a new online order',
            'target_item' => $this->order->order_number,
            'order_id' => $this->order->getKey(),
            'url' => route('online-store.settings'),
        ];
    }
}

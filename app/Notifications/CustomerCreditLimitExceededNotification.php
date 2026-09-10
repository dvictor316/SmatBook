<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CustomerCreditLimitExceededNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Customer $customer,
        private readonly array $creditWarning,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $customerName = $this->creditWarning['customer'] ?? $this->customer->customer_name ?? $this->customer->name ?? 'Customer';
        $creditLimit = (float) ($this->creditWarning['credit_limit'] ?? 0);
        $projectedOutstanding = (float) ($this->creditWarning['projected_outstanding'] ?? 0);

        return [
            'type' => 'customer_credit_limit_exceeded',
            'title' => 'Customer credit limit exceeded',
            'message' => "{$customerName} would exceed the approved credit limit.",
            'customer_id' => $this->customer->id,
            'customer_name' => $customerName,
            'credit_limit' => $creditLimit,
            'projected_outstanding' => $projectedOutstanding,
            'url' => '/customers/' . $this->customer->id,
        ];
    }
}
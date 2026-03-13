<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire-due';
    protected $description = 'Mark due subscriptions as expired.';

    public function handle(): int
    {
        $count = method_exists(Subscription::class, 'expireDueSubscriptions')
            ? Subscription::expireDueSubscriptions()
            : 0;
        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}

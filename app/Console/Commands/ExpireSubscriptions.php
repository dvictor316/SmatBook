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
        $count = Subscription::expireDueSubscriptions();
        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}


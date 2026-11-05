<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExpireDemoAccounts extends Command
{
    protected $signature   = 'demo:expire-accounts';
    protected $description = 'Legacy command retained for compatibility; demo accounts no longer expire automatically.';

    public function handle(): int
    {
        $this->info('Automatic demo expiry is disabled. No demo accounts were changed.');
        return self::SUCCESS;
    }
}

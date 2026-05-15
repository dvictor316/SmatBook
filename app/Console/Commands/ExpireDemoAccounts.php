<?php

namespace App\Console\Commands;

use App\Models\DemoRequest;
use App\Services\DemoProvisioningService;
use Illuminate\Console\Command;

class ExpireDemoAccounts extends Command
{
    protected $signature   = 'demo:expire-accounts';
    protected $description = 'Mark expired demo requests and deactivate expired demo users/companies.';

    public function __construct(private DemoProvisioningService $provisioner)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking for expired demo accounts...');

        // Find approved demo requests whose expiry has passed
        $expired = DemoRequest::where('status', 'approved')
            ->where('expires_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired demo accounts found.');
            return self::SUCCESS;
        }

        foreach ($expired as $demoRequest) {
            $this->info("Expiring demo for: {$demoRequest->email}");
            $this->provisioner->deactivateExpiredDemo($demoRequest);
        }

        $this->info("Expired {$expired->count()} demo account(s).");
        return self::SUCCESS;
    }
}

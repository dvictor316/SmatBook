<?php

namespace App\Jobs;

use App\Services\RecurringInvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessRecurringInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts before marking as failed.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Seconds after which the job should time out.
     */
    public int $timeout = 120;

    public function handle(RecurringInvoiceService $service): void
    {
        Log::info('RecurringInvoicesJob: starting scheduled run.');

        try {
            $service->processAllDue();
            Log::info('RecurringInvoicesJob: completed successfully.');
        } catch (Throwable $e) {
            Log::error('RecurringInvoicesJob: unhandled error – ' . $e->getMessage());
            throw $e; // let the queue driver handle retry
        }
    }
}

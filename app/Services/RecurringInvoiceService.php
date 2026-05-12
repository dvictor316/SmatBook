<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\RecurringInvoiceLog;
use App\Models\RecurringInvoiceTemplate;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\LedgerService;
use App\Support\SystemEventMailer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RecurringInvoiceService
{
    // ─── Public entry-points ──────────────────────────────────────────────────

    /**
     * Process every template that is due today.
     * Safe to call from scheduler or queue – idempotent per scheduled_date.
     */
    public function processAllDue(): void
    {
        RecurringInvoiceTemplate::query()
            ->where('status', 'active')
            ->where('next_run_on', '<=', today()->toDateString())
            ->orderBy('next_run_on')
            ->chunk(50, function ($templates) {
                foreach ($templates as $template) {
                    $this->processSingle($template, 'scheduler');
                }
            });
    }

    /**
     * Manually trigger a single template (from controller).
     *
     * @throws \RuntimeException on failure
     */
    public function runManual(RecurringInvoiceTemplate $template): Sale
    {
        if (!$template->isActive()) {
            throw new \RuntimeException('Template is not active and cannot be run.');
        }

        $scheduledDate = $template->next_run_on ?? today();

        return $this->generate($template, $scheduledDate, 'manual');
    }

    // ─── Core generation ──────────────────────────────────────────────────────

    /**
     * Generate one invoice for the given template + scheduled date.
     * Idempotency: duplicate (template_id, scheduled_date) is silently skipped.
     *
     * @throws \RuntimeException on database / service failure
     */
    public function generate(
        RecurringInvoiceTemplate $template,
        Carbon $scheduledDate,
        string $generatedBy = 'scheduler'
    ): Sale {
        // ── Idempotency guard ─────────────────────────────────────────────────
        $alreadyRan = RecurringInvoiceLog::query()
            ->where('template_id', $template->id)
            ->whereDate('scheduled_date', $scheduledDate->toDateString())
            ->where('status', 'success')
            ->exists();

        if ($alreadyRan) {
            throw new \RuntimeException(
                "Invoice for template #{$template->id} on {$scheduledDate->toDateString()} was already generated."
            );
        }

        return DB::transaction(function () use ($template, $scheduledDate, $generatedBy) {

            // ── Build the Sale ────────────────────────────────────────────────
            $invoiceDate = $scheduledDate->copy();
            $dueDate     = $invoiceDate->copy()->addDays((int) ($template->due_days ?? 30));

            $invoiceNo = $this->generateInvoiceNumber($template);

            $sale = Sale::create([
                'company_id'     => $template->company_id,
                'branch_id'      => $template->branch_id,
                'branch_name'    => $template->branch_name,
                'invoice_no'     => $invoiceNo,
                'order_date'     => $invoiceDate->toDateString(),
                'delivery_date'  => $dueDate->toDateString(),
                'customer_id'    => $template->customer_id,
                'customer_name'  => $template->customer_name,
                'user_id'        => $template->created_by,
                'subtotal'       => $template->subtotal,
                'tax'            => $template->tax_amount,
                'discount'       => $template->discount,
                'total'          => $template->total,
                'paid'           => 0,
                'amount_paid'    => 0,
                'balance'        => $template->total,
                'currency'       => $template->currency ?? 'NGN',
                'payment_method' => 'invoice',
                'payment_status' => 'unpaid',
                'order_status'   => 'pending',
                'payment_details' => [
                    'source'             => 'recurring_invoice',
                    'template_id'        => $template->id,
                    'template_name'      => $template->template_name,
                    'scheduled_date'     => $scheduledDate->toDateString(),
                    'terms'              => $template->terms,
                    'payment_instructions' => $template->payment_instructions,
                    'internal_memo'      => $template->internal_memo,
                ],
            ]);

            // ── Create line items ─────────────────────────────────────────────
            $items = $template->items ?? [];
            foreach ($items as $item) {
                $qty       = (float) ($item['qty'] ?? $item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? $item['price'] ?? 0);
                $itemTax   = (float) ($item['tax'] ?? 0);
                $itemDisc  = (float) ($item['discount'] ?? 0);
                $subtotal  = round($qty * $unitPrice, 2);
                $total     = round($subtotal + $itemTax - $itemDisc, 2);

                SaleItem::create([
                    'company_id'   => $template->company_id,
                    'branch_id'    => $template->branch_id,
                    'branch_name'  => $template->branch_name,
                    'sale_id'      => $sale->id,
                    'product_id'   => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'] ?? $item['name'] ?? 'Service',
                    'qty'          => (int) $qty,
                    'unit_price'   => $unitPrice,
                    'discount'     => $itemDisc,
                    'tax'          => $itemTax,
                    'subtotal'     => $subtotal,
                    'total_price'  => $total,
                ]);
            }

            // ── Post accounting entries ───────────────────────────────────────
            LedgerService::postSale($sale->fresh());

            // ── Log the generation ────────────────────────────────────────────
            RecurringInvoiceLog::create([
                'template_id'    => $template->id,
                'sale_id'        => $sale->id,
                'scheduled_date' => $scheduledDate->toDateString(),
                'status'         => 'success',
                'generated_by'   => $generatedBy,
                'message'        => "Invoice {$invoiceNo} generated successfully.",
            ]);

            // ── Advance the template state ────────────────────────────────────
            $nextRunOn = $this->calculateNextRunDate($template, $scheduledDate);
            $newCount  = $template->occurrences_count + 1;
            $newStatus = $template->status;

            if ($template->isExpired()) {
                $newStatus = 'completed';
            } elseif ($nextRunOn === null) {
                $newStatus = 'completed';
            } elseif ($template->end_type === 'count' && $newCount >= (int) $template->max_occurrences) {
                $newStatus = 'completed';
                $nextRunOn = null;
            }

            $template->update([
                'last_run_on'       => now(),
                'next_run_on'       => $nextRunOn?->toDateString(),
                'occurrences_count' => $newCount,
                'status'            => $newStatus,
            ]);

            // ── Send email if configured ──────────────────────────────────────
            if ($template->automation_mode === 'auto_send' && $template->send_email) {
                $this->sendInvoiceEmail($sale, $template);
            }

            return $sale;
        });
    }

    /**
     * Safe wrapper: catches all errors, writes a failure log, and re-throws.
     */
    public function processSingle(
        RecurringInvoiceTemplate $template,
        string $generatedBy = 'scheduler'
    ): void {
        if ($template->isExpired()) {
            $template->update(['status' => 'completed']);
            return;
        }

        $scheduledDate = $template->next_run_on ?? today();

        try {
            $this->generate($template, $scheduledDate, $generatedBy);
        } catch (\RuntimeException $e) {
            // Already generated (idempotency) – not a real failure
            Log::info("RecurringInvoice: skipped template #{$template->id} – {$e->getMessage()}");
        } catch (Throwable $e) {
            Log::error("RecurringInvoice: FAILED template #{$template->id} – {$e->getMessage()}");

            // Write failure log (ignore unique constraint if it already exists)
            try {
                RecurringInvoiceLog::firstOrCreate(
                    [
                        'template_id'    => $template->id,
                        'scheduled_date' => $scheduledDate->toDateString(),
                    ],
                    [
                        'status'       => 'failed',
                        'generated_by' => $generatedBy,
                        'message'      => Str::limit($e->getMessage(), 500),
                    ]
                );
            } catch (Throwable) {
                // suppress secondary log error
            }
        }
    }

    // ─── Next-run-date calculator ─────────────────────────────────────────────

    public function calculateNextRunDate(
        RecurringInvoiceTemplate $template,
        ?Carbon $fromDate = null
    ): ?Carbon {
        $base = ($fromDate ?? ($template->next_run_on ?? today()))->copy();

        $next = match ($template->frequency) {
            'daily'       => $base->addDays(1),
            'weekly'      => $base->addWeeks(1),
            'biweekly'    => $base->addWeeks(2),
            'monthly'     => $base->addMonths(1),
            'quarterly'   => $base->addMonths(3),
            'semi_annual' => $base->addMonths(6),
            'annual'      => $base->addYear(),
            'custom'      => $this->applyCustomInterval($base, $template),
            default       => $base->addMonths(1),
        };

        // Apply date_rule adjustments
        $next = $this->applyDateRule($next, $template);

        // Skip weekends if configured
        if ($template->skip_weekends) {
            while ($next->isWeekend()) {
                $next->addDay();
            }
        }

        // Respect end_type = date
        if ($template->end_type === 'date' && $template->ends_on && $next->gt($template->ends_on)) {
            return null;
        }

        return $next;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function applyCustomInterval(Carbon $base, RecurringInvoiceTemplate $template): Carbon
    {
        $value = max(1, (int) $template->interval_value);
        return match ($template->interval_unit) {
            'days'   => $base->addDays($value),
            'weeks'  => $base->addWeeks($value),
            'months' => $base->addMonths($value),
            'years'  => $base->addYears($value),
            default  => $base->addMonths($value),
        };
    }

    private function applyDateRule(Carbon $date, RecurringInvoiceTemplate $template): Carbon
    {
        return match ($template->date_rule) {
            'first_of_month' => $date->startOfMonth(),
            'last_of_month'  => $date->endOfMonth()->startOfDay(),
            'specific_day'   => $this->resolveSpecificDay($date, (int) ($template->specific_day ?? 1)),
            default          => $date,
        };
    }

    private function resolveSpecificDay(Carbon $date, int $day): Carbon
    {
        $day = max(1, min(28, $day)); // cap at 28 to avoid month overflow
        $candidate = $date->copy()->day($day);
        // If we've already passed that day this month, move to next month
        if ($candidate->lt($date)) {
            $candidate->addMonth()->day($day);
        }
        return $candidate;
    }

    private function generateInvoiceNumber(RecurringInvoiceTemplate $template): string
    {
        $prefix = 'RINV-' . now()->format('ymd') . '-';
        do {
            $candidate = $prefix . strtoupper(Str::random(5));
        } while (Sale::withTrashed()->where('invoice_no', $candidate)->exists());

        return $candidate;
    }

    private function sendInvoiceEmail(Sale $sale, RecurringInvoiceTemplate $template): void
    {
        try {
            $customer  = Customer::find($template->customer_id);
            $recipient = $customer?->email;

            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            $subject = $template->email_subject
                ?: "Invoice {$sale->invoice_no} from " . config('app.name');

            SystemEventMailer::sendMessage(
                $recipient,
                $subject,
                'Recurring Invoice',
                $template->payment_instructions
                    ?: 'Your recurring invoice is ready. Please make payment by the due date.',
                [
                    'Customer'       => $sale->display_customer_name,
                    'Invoice Number' => $sale->invoice_no,
                    'Invoice Date'   => optional($sale->order_date)->format('d M Y'),
                    'Due Date'       => optional($sale->delivery_date)->format('d M Y'),
                    'Total Amount'   => ($sale->currency ?? 'NGN') . ' ' . number_format((float) $sale->total, 2),
                ]
            );
        } catch (Throwable $e) {
            Log::warning("RecurringInvoice: email failed for sale #{$sale->id} – {$e->getMessage()}");
        }
    }
}

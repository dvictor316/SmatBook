<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Carbon\Carbon;

trait HasUniqueReceiptNumber
{
    /**
     * Generate a unique reference for a model column.
     */
    protected function generateUniqueReference(string $modelClass, string $column, string $prefix, int $randomLength = 6, bool $checkTrashed = false): string
    {
        do {
            $candidate = $prefix . strtoupper(Str::random($randomLength));
            $query = $modelClass::where($column, $candidate);
            
            if ($checkTrashed && in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass))) {
                $query->withTrashed();
            }
        } while ($query->exists());

        return $candidate;
    }

    /**
     * Generate unique receipt number for Payment model.
     */
    protected function generatePaymentReceiptNo(): string
    {
        $prefix = 'RCPT-' . time() . '-';
        return $this->generateUniqueReference(\App\Models\Payment::class, 'receipt_no', $prefix, 4);
    }

    /**
     * Generate unique receipt number for Sale model.
     */
    protected function generateSaleReceiptNo(): string
    {
        $prefix = 'REC-' . Carbon::now()->format('ymd') . '-';
        return $this->generateUniqueReference(\App\Models\Sale::class, 'receipt_no', $prefix, 6, true);
    }

    protected function generateSaleInvoiceNo(): string
    {
        $prefix = 'INV-' . Carbon::now()->format('ymd') . '-';
        return $this->generateUniqueReference(\App\Models\Sale::class, 'invoice_no', $prefix, 6, true);
    }

    protected function generateSaleOrderNo(): string
    {
        $prefix = 'ORD-' . Carbon::now()->format('Ymd') . '-';
        return $this->generateUniqueReference(\App\Models\Sale::class, 'order_number', $prefix, 6, true);
    }
}
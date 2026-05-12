<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringInvoiceLog extends Model
{
    use HasFactory;

    protected $table = 'recurring_invoice_logs';

    protected $fillable = [
        'template_id',
        'sale_id',
        'event_type',
        'scheduled_date',
        'status',
        'generated_by',
        'started_at',
        'finished_at',
        'message',
        'payload',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'payload' => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function template(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoiceTemplate::class, 'template_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'success' => 'bg-success-light',
            'failed'  => 'bg-danger-light',
            'processing' => 'bg-info-light',
            'skipped' => 'bg-warning-light',
            default   => 'bg-secondary',
        };
    }
}

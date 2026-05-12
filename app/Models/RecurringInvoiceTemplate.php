<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class RecurringInvoiceTemplate extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'recurring_invoice_templates';

    protected $fillable = [
        'company_id', 'branch_id', 'branch_name',
        'created_by', 'updated_by',
        'customer_id', 'customer_name',
        'template_name', 'notes', 'internal_memo',
        'salesperson_id', 'payment_instructions',
        'currency', 'terms', 'due_days',
        'frequency', 'interval_value', 'interval_unit',
        'date_rule', 'specific_day', 'skip_weekends',
        'automation_mode',
        'starts_on', 'next_run_on', 'last_run_on',
        'end_type', 'ends_on', 'max_occurrences', 'occurrences_count',
        'status',
        'items',
        'subtotal', 'tax_amount', 'discount', 'total',
        'send_email', 'email_subject',
        'reminder_before_days', 'reminder_after_days',
    ];

    protected $casts = [
        'starts_on'            => 'date',
        'next_run_on'          => 'date',
        'last_run_on'          => 'datetime',
        'ends_on'              => 'date',
        'items'                => 'array',
        'reminder_before_days' => 'array',
        'reminder_after_days'  => 'array',
        'skip_weekends'        => 'boolean',
        'send_email'           => 'boolean',
        'subtotal'             => 'decimal:2',
        'tax_amount'           => 'decimal:2',
        'discount'             => 'decimal:2',
        'total'                => 'decimal:2',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RecurringInvoiceLog::class, 'template_id')->latest();
    }

    public function generatedSales(): HasMany
    {
        return $this->hasMany(RecurringInvoiceLog::class, 'template_id')
                    ->whereNotNull('sale_id')
                    ->latest();
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            'daily'        => 'Daily',
            'weekly'       => 'Weekly',
            'biweekly'     => 'Every 2 Weeks',
            'monthly'      => 'Monthly',
            'quarterly'    => 'Every 3 Months',
            'semi_annual'  => 'Every 6 Months',
            'annual'       => 'Annually',
            'custom'       => "Every {$this->interval_value} {$this->interval_unit}",
            default        => ucfirst($this->frequency),
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'bg-success-light',
            'paused'    => 'bg-warning-light',
            'completed' => 'bg-info-light',
            'cancelled' => 'bg-danger-light',
            'archived'  => 'bg-secondary',
            default     => 'bg-secondary',
        };
    }

    public function getAutomationLabelAttribute(): string
    {
        return match ($this->automation_mode) {
            'draft'          => 'Draft (review before send)',
            'auto_send'      => 'Auto Send',
            'reminder_only'  => 'Reminder Only',
            'manual'         => 'Manual',
            default          => ucfirst($this->automation_mode),
        };
    }

    public function getDisplayCustomerNameAttribute(): string
    {
        return $this->customer?->customer_name
            ?? $this->customer?->name
            ?? $this->customer_name
            ?? 'Walk-in Customer';
    }

    public function getRemainingCyclesAttribute(): ?int
    {
        if ($this->end_type !== 'count' || $this->max_occurrences === null) {
            return null;
        }
        return max(0, $this->max_occurrences - $this->occurrences_count);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDue(): bool
    {
        if (!$this->isActive()) {
            return false;
        }
        if ($this->next_run_on === null) {
            return false;
        }
        return $this->next_run_on->lte(now()->startOfDay());
    }

    public function isExpired(): bool
    {
        if ($this->end_type === 'date' && $this->ends_on !== null && $this->ends_on->lt(today())) {
            return true;
        }
        if ($this->end_type === 'count' && $this->max_occurrences !== null && $this->occurrences_count >= $this->max_occurrences) {
            return true;
        }
        return false;
    }
}

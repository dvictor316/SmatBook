<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheque extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'cheque_number', 'type', 'bank_account_id',
        'payee_name', 'drawer_name', 'amount', 'currency', 'cheque_date', 'due_date',
        'status', 'supplier_id', 'customer_id', 'bank_id', 'notes',
        'transaction_id', 'created_by',
    ];

    protected $casts = [
        'cheque_date' => 'date',
        'due_date'    => 'date',
        'amount'      => 'decimal:2',
    ];

    public function company(): BelongsTo   { return $this->belongsTo(Company::class); }
    public function supplier(): BelongsTo  { return $this->belongsTo(Supplier::class); }
    public function customer(): BelongsTo  { return $this->belongsTo(Customer::class); }
    public function bank(): BelongsTo      { return $this->belongsTo(Bank::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeIssued($query)   { return $query->where('type', 'issue'); }
    public function scopeReceived($query) { return $query->where('type', 'receive'); }
    public function scopePending($query)  { return $query->where('status', 'pending'); }
    public function scopeCleared($query)  { return $query->where('status', 'cleared'); }
    public function scopeBounced($query)  { return $query->where('status', 'bounced'); }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'cleared'   => 'badge-success',
            'bounced'   => 'badge-danger',
            'cancelled', 'voided' => 'badge-secondary',
            'deposited' => 'badge-info',
            default     => 'badge-warning',
        };
    }
}

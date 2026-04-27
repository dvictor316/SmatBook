<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'branch_id', 'loan_number', 'type', 'lender_name', 'bank_id',
        'principal_amount', 'interest_rate', 'interest_type', 'disbursement_date',
        'maturity_date', 'tenure_months', 'repayment_frequency', 'emi_amount',
        'outstanding_balance', 'status', 'account_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'disbursement_date'  => 'date',
        'maturity_date'      => 'date',
        'principal_amount'   => 'decimal:2',
        'interest_rate'      => 'decimal:4',
        'emi_amount'         => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
    ];

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function bank(): BelongsTo       { return $this->belongsTo(Bank::class); }
    public function account(): BelongsTo    { return $this->belongsTo(Account::class); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function repayments(): HasMany   { return $this->hasMany(LoanRepayment::class); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopeActive($query)   { return $query->where('status', 'active'); }
    public function scopeClosed($query)   { return $query->where('status', 'closed'); }

    public function getTotalRepaidAttribute(): float
    {
        return (float) $this->repayments()->sum('total_paid');
    }
}

class LoanRepayment extends Model
{
    protected $table = 'loan_repayments';

    protected $fillable = [
        'loan_id', 'company_id', 'payment_date', 'principal_paid',
        'interest_paid', 'total_paid', 'payment_method', 'reference',
        'notes', 'created_by',
    ];

    protected $casts = [
        'payment_date'    => 'date',
        'principal_paid'  => 'decimal:2',
        'interest_paid'   => 'decimal:2',
        'total_paid'      => 'decimal:2',
    ];

    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanRepayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'loan_id', 'payment_date', 'principal_amount', 'interest_amount',
        'total_amount', 'payment_method', 'reference', 'notes',
        'company_id', 'branch_id', 'created_by',
    ];

    protected $casts = [
        'payment_date'     => 'date',
        'principal_amount' => 'decimal:2',
        'interest_amount'  => 'decimal:2',
        'total_amount'     => 'decimal:2',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}

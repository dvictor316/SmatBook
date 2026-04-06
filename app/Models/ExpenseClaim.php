<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseClaim extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'branch_id',
        'branch_name',
        'user_id',
        'project_id',
        'title',
        'expense_date',
        'amount',
        'category',
        'notes',
        'status',
        'reimbursement_status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'reimbursement_account_id',
        'reimbursed_expense_id',
        'reimbursed_by',
        'reimbursed_at',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'float',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'reimbursed_at' => 'datetime',
    ];

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reimburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reimbursed_by');
    }

    public function reimbursementAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'reimbursement_account_id');
    }

    public function reimbursedExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'reimbursed_expense_id');
    }
}

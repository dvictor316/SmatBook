<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntercompanyTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'counterparty_company_id', 'reference_number', 'transaction_type',
        'transaction_date', 'amount', 'currency', 'status', 'source_account_id',
        'target_account_id', 'branch_id', 'description', 'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
    ];

    public function company(): BelongsTo             { return $this->belongsTo(Company::class); }
    public function counterpartyCompany(): BelongsTo { return $this->belongsTo(Company::class, 'counterparty_company_id'); }
    public function sourceAccount(): BelongsTo       { return $this->belongsTo(Account::class, 'source_account_id'); }
    public function targetAccount(): BelongsTo       { return $this->belongsTo(Account::class, 'target_account_id'); }

    public function scopeForCompany($query, int $companyId) { return $query->where('company_id', $companyId); }
    public function scopePending($query) { return $query->where('status', 'draft'); }
    public function scopePosted($query)  { return $query->where('status', 'posted'); }
}

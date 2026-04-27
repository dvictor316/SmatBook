<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
        'bank_statement_import_id',
        'bank_id',
        'matched_transaction_id',
        'line_date',
        'description',
        'reference',
        'debit',
        'credit',
        'amount',
        'balance',
        'status',
        'matched_at',
        'matched_by',
        'review_notes',
        'raw_row',
    ];

    protected $casts = [
        'line_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'matched_at' => 'datetime',
        'raw_row' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function matchedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'matched_transaction_id');
    }

    public function matcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }
}

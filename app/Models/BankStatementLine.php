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
        'line_date',
        'description',
        'reference',
        'debit',
        'credit',
        'amount',
        'balance',
        'status',
        'raw_row',
    ];

    protected $casts = [
        'line_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
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
}

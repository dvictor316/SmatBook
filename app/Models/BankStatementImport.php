<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
        'bank_id',
        'uploaded_by',
        'source_file_name',
        'stored_file_path',
        'currency',
        'statement_date_from',
        'statement_date_to',
        'line_count',
        'opening_balance',
        'closing_balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'statement_date_from' => 'date',
        'statement_date_to' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }
}

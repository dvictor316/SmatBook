<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class LivestockOpexEntry extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'farm_id', 'flock_id', 'expense_date', 'category', 'description', 'quantity', 'unit', 'unit_cost', 'amount', 'frequency', 'vendor', 'reference', 'created_by', 'debit_account_id', 'credit_account_id', 'journal_reference', 'posted_at', 'reversed_at'];

    protected $casts = ['expense_date' => 'date', 'quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'amount' => 'decimal:2', 'posted_at' => 'datetime', 'reversed_at' => 'datetime'];

    public function flock()
    {
        return $this->belongsTo(LivestockFlock::class, 'flock_id');
    }
}

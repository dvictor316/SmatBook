<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class LivestockRevenueEntry extends Model
{
    use TenantScoped;

    protected $fillable = ['company_id', 'farm_id', 'flock_id', 'revenue_date', 'source', 'description', 'quantity', 'unit', 'unit_price', 'amount', 'customer', 'reference', 'created_by', 'debit_account_id', 'credit_account_id', 'journal_reference', 'posted_at', 'reversed_at'];

    protected $casts = ['revenue_date' => 'date', 'quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'amount' => 'decimal:2', 'posted_at' => 'datetime', 'reversed_at' => 'datetime'];

    public function flock()
    {
        return $this->belongsTo(LivestockFlock::class, 'flock_id');
    }
}

<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'branch_name', 'created_by', 'reference',
        'provider', 'country', 'category_code', 'biller_code', 'item_code',
        'product_name', 'customer_identifier', 'customer_name', 'amount',
        'provider_fee', 'commission_earned', 'currency', 'status',
        'provider_reference', 'provider_transaction_id', 'recharge_token',
        'failure_reason', 'provider_response', 'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_fee' => 'decimal:2',
        'commission_earned' => 'decimal:2',
        'provider_response' => 'array',
        'processed_at' => 'datetime',
    ];
}

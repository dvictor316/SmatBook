<?php

namespace App\Models;

use App\Models\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

class OnlineStoreOrder extends Model
{
    use TenantScoped;

    protected $fillable = [
        'company_id', 'branch_id', 'branch_name', 'order_number', 'customer_name',
        'customer_email', 'customer_phone', 'delivery_address', 'payment_method',
        'status', 'subtotal', 'total_amount', 'customer_note',
    ];

    protected $casts = ['subtotal' => 'decimal:2', 'total_amount' => 'decimal:2'];

    public function items()
    {
        return $this->hasMany(OnlineStoreOrderItem::class);
    }
}

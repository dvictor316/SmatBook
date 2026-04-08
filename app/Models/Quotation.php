<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Quotation extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'quotation_id',
        'customer_id',
        'customer_name',
        'company_id',
        'user_id',
        'branch_id',
        'branch_name',
        'issue_date',
        'expiry_date',
        'subtotal',
        'tax',
        'discount',
        'total',
        'status',
        'description',
        'items_json',
        'note',
        'created_at'
    ];

    /**
     * Get the customer associated with the quotation.
     */
    public function customer()
    {
        // Assumes your customer table is 'customers' and foreign key is 'customer_id'
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'customer_id',
        'total',
        'status',
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estimate extends Model
{
    use HasFactory;

    protected $fillable = [
        'estimate_number',
        'customer_id',
        'issue_date',
        'expiry_date',
        'subtotal',
        'tax',
        'discount',
        'total_amount',
        'status',
        'notes',
        'company_id',
        'user_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Accessors
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'Sent':
                return 'success';
            case 'Draft':
                return 'warning';
            case 'Expired':
                return 'danger';
            case 'Accepted':
                return 'primary';
            case 'Declined':
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    public function getAmountAttribute()
    {
        return $this->total_amount;
    }
}

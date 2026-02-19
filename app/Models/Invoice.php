<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Import this

class Invoice extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'customer_id',    // <-- Add this back to the fillable array
        'company_id',
        'plan_id',
        'amount',
        'total',
        'total_amount',
        'status',
        'description',
        'expenses',
        'product_id',
        'product_name',
        'invoice_date',
        'due_date',
    ];

    // Define the relationship to the Customer model
    public function customer(): BelongsTo // <-- Add this relationship function
    {
        return $this->belongsTo(Customer::class);
    }

    // Relationship to Company
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Relationship to Plan (if applicable)
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}

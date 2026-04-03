<?php

// In app/Models/Receipt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class Receipt extends Model
{
    use TenantScoped;
    // If your table name is not 'receipts' (unlikely), set it here:
    // protected $table = 'your_custom_receipt_table_name'; 

    protected $fillable = [
        'customer_id',
        'amount', // Ensure this matches the column you use for the sum!
        'date',
        // ... other columns
    ];
}

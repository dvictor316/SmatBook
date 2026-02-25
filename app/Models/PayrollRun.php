<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'period', 'pay_date', 'payment_method',
        'notes', 'status', 'staff_count',
        'total_amount', 'business_id',
    ];

    protected $casts = [
        'pay_date'     => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }
}

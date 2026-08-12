<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\TenantScoped;

class GuestFolio extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'company_id','property_id','stay_id','reservation_id','customer_id','folio_number','opening_deposit','total_charges','total_payments','balance','status'
    ];

    public function stay()
    {
        return $this->belongsTo(Stay::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(FolioItem::class, 'folio_id');
    }

}

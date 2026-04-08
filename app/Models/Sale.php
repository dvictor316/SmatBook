<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Support\GeoCurrency;

class Sale extends Model
{
    use Multitenantable, HasFactory, SoftDeletes;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'company_id', 'branch_id', 'branch_name',
        'order_number', 'invoice_no', 'receipt_no', 'order_date', 'delivery_date',
        'customer_id', 'customer_name', 'user_id', 'terminal_id', 
        'subtotal', 'discount', 'tax', 'shipping_cost', 'total', 
        'paid', 'amount_paid', 'change_amount', 'balance', 
        'currency', 'amount_in_words', 'payment_method', 
        'payment_status', 'payment_details', 'order_status'
    ];

    /**
     * Type casting for attributes.
     */
    protected $casts = [
        'order_date'      => 'date',
        'delivery_date'   => 'date',
        'subtotal'        => 'decimal:2',
        'tax'             => 'decimal:2',
        'discount'        => 'decimal:2',
        'shipping_cost'   => 'decimal:2',
        'total'           => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'balance'         => 'decimal:2',
        'payment_details' => 'array',
    ];

    /**
     * Auto-generate Order Number on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (Schema::hasColumn('sales', 'order_number') && empty($sale->order_number)) {
                $sale->order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            }
            if (Schema::hasColumn('sales', 'receipt_no') && empty($sale->receipt_no)) {
                do {
                    $receiptNumber = 'REC-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
                } while (self::where('receipt_no', $receiptNumber)->exists());

                $sale->receipt_no = $receiptNumber;
            }
            if (Schema::hasColumn('sales', 'order_date') && empty($sale->order_date)) {
                $sale->order_date = today();
            }
        });
    }

    /**
     * RELATIONSHIPS
     */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest();
    }

    /**
     * SCOPES
     */

    public function scopePending($query) { return $query->where('order_status', 'pending'); }
    public function scopeCompleted($query) { return $query->where('order_status', 'completed'); }
    public function scopeToday($query) { return $query->whereDate('order_date', today()); }
    public function scopeThisMonth($query) {
        return $query->whereMonth('order_date', now()->month)->whereYear('order_date', now()->year);
    }

    /**
     * CALCULATIONS & LOGIC
     */

    public function calculateTotal()
    {
        $calculatedSubtotal = $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $this->subtotal = $calculatedSubtotal;
        $this->total = ($calculatedSubtotal + $this->tax + ($this->shipping_cost ?? 0)) - ($this->discount ?? 0);
        
        return $this->total;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid' || $this->amount_paid >= $this->total;
    }

    public function markAsPaid()
    {
        $this->update([
            'payment_status' => 'paid',
            'order_status'   => 'completed',
            'amount_paid'    => $this->total,
            'balance'        => 0
        ]);
    }

    /**
     * ACCESSORS
     */

    public function getPaidAmountSumAttribute()
    {
        return $this->payments->where('status', 'success')->sum('amount');
    }

    public function getDueAmountAttribute()
    {
        return $this->total - $this->amount_paid;
    }

    public function getDisplayCustomerNameAttribute(): string
    {
        return $this->customer?->customer_name
            ?? $this->customer?->name
            ?? $this->customer_name
            ?? 'Walk-in Customer';
    }

    public function getBranchLabelAttribute(): ?string
    {
        if (!empty($this->attributes['branch_name'] ?? null)) {
            return (string) $this->attributes['branch_name'];
        }

        $details = $this->payment_details;

        if (is_string($details)) {
            $details = json_decode($details, true);
        }

        if (!is_array($details)) {
            return null;
        }

        return $details['branch_name']
            ?? data_get($details, 'branch.name')
            ?? null;
    }

    public function getBranchIdAttribute(): ?string
    {
        if (!empty($this->attributes['branch_id'] ?? null)) {
            return (string) $this->attributes['branch_id'];
        }

        $details = $this->payment_details;

        if (is_string($details)) {
            $details = json_decode($details, true);
        }

        if (!is_array($details)) {
            return null;
        }

        $branchId = $details['branch_id']
            ?? data_get($details, 'branch.id');

        return $branchId !== null ? (string) $branchId : null;
    }

    public function getAmountInWordsDisplayAttribute(): string
    {
        if (!empty($this->amount_in_words)) {
            return (string) $this->amount_in_words;
        }

        return $this->convertNumberToWords((float) $this->total);
    }

    public function getFormattedTotalAttribute()
    {
        $sourceCurrency = GeoCurrency::normalizeCurrency((string) ($this->currency ?: 'NGN'));
        return GeoCurrency::format((float) $this->total, $sourceCurrency);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending'    => 'badge-warning',
            'processing' => 'badge-info',
            'completed'  => 'badge-success',
            'cancelled'  => 'badge-danger',
        ];
        return '<span class="badge ' . ($badges[$this->order_status] ?? 'badge-secondary') . '">' . ucfirst($this->order_status) . '</span>';
    }

    private function convertNumberToWords(float $number): string
    {
        $wholeNumber = (int) round($number);

        if ($wholeNumber === 0) {
            return 'Zero Naira Only';
        }

        return ucfirst(trim($this->spellOutNumber($wholeNumber))) . ' Naira Only';
    }

    private function spellOutNumber(int $number): string
    {
        $dictionary = [
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety',
        ];

        if ($number < 21) {
            return $dictionary[$number];
        }

        if ($number < 100) {
            $tens = ((int) floor($number / 10)) * 10;
            $units = $number % 10;

            return $dictionary[$tens] . ($units ? '-' . $dictionary[$units] : '');
        }

        if ($number < 1000) {
            $hundreds = (int) floor($number / 100);
            $remainder = $number % 100;

            return $dictionary[$hundreds] . ' hundred' . ($remainder ? ' and ' . $this->spellOutNumber($remainder) : '');
        }

        if ($number < 1000000) {
            $thousands = (int) floor($number / 1000);
            $remainder = $number % 1000;

            return $this->spellOutNumber($thousands) . ' thousand' . ($remainder ? ($remainder < 100 ? ' and ' : ', ') . $this->spellOutNumber($remainder) : '');
        }

        if ($number < 1000000000) {
            $millions = (int) floor($number / 1000000);
            $remainder = $number % 1000000;

            return $this->spellOutNumber($millions) . ' million' . ($remainder ? ($remainder < 100 ? ' and ' : ', ') . $this->spellOutNumber($remainder) : '');
        }

        $billions = (int) floor($number / 1000000000);
        $remainder = $number % 1000000000;

        return $this->spellOutNumber($billions) . ' billion' . ($remainder ? ($remainder < 100 ? ' and ' : ', ') . $this->spellOutNumber($remainder) : '');
    }
}

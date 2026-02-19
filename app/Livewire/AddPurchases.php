<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Bank;

class AddPurchases extends Component
{
    use WithFileUploads;

    // Form properties
    public $purchaseId, $purchaseDate, $dueDate, $referenceNo, $invoiceSerialNo;
    public $notes, $termsConditions, $signatureName;
    public $signatureImage;

    public $vendors = [], $products = [], $taxOptions = [], $banks = [];
    public $selectedVendor, $selectedProduct, $selectedTax, $selectedBank;

    public $purchaseItems = []; // Array of items: each item is an associative array
    public $discountType = 'percentage', $discountValue = 0;
    public $roundOff = false, $roundOffAmount = 0;
    public $totalTaxableAmount = 0, $totalDiscountAmount = 0, $vatAmount = 0, $totalAmount = 0;

    protected $rules = [
        'purchaseId' => 'required|string',
        'purchaseDate' => 'required|date',
        'dueDate' => 'required|date|after_or_equal:purchaseDate',
        'referenceNo' => 'nullable|string',
        'invoiceSerialNo' => 'nullable|string',
        'notes' => 'nullable|string',
        'termsConditions' => 'nullable|string',
        'signatureName' => 'nullable|string',
        'signatureImage' => 'nullable|image|max:1024', // max 1MB
        'selectedVendor' => 'required|exists:vendors,id',
        'selectedProduct' => 'nullable|exists:products,id',
        'selectedTax' => 'nullable|exists:taxes,id',
        'selectedBank' => 'nullable|exists:banks,id',
        'discountValue' => 'numeric|min:0',
    ];

    public function mount()
    {
        $this->vendors = Vendor::all();
        $this->products = Product::all();
        $this->taxOptions = Tax::all();
        $this->banks = Bank::all();

        // Initialize with one empty item
        $this->addItem();
    }

    // Add a new empty item
    public function addItem()
    {
        $this->purchaseItems[] = [
            'product_id' => null,
            'product_name' => '',
            'quantity' => 1,
            'unit' => '',
            'rate' => 0,
            'discount' => 0,
            'tax_id' => null,
        ];
    }

    // Remove item at specific index
    public function removeItem($index)
    {
        unset($this->purchaseItems[$index]);
        $this->purchaseItems = array_values($this->purchaseItems);
        $this->calculateTotals();
    }

    // When a product is selected, populate product_name
    public function updatedPurchaseItems($value, $key)
    {
        // key example: '0.product_id'
        if (str_ends_with($key, 'product_id')) {
            $index = explode('.', $key)[0];
            $productId = $value;
            $product = Product::find($productId);
            if ($product) {
                $this->purchaseItems[$index]['product_name'] = $product->name;
            } else {
                $this->purchaseItems[$index]['product_name'] = '';
            }
        }
        $this->calculateTotals();
    }

    // Calculate totals based on items
    public function calculateTotals()
    {
        $taxableAmount = 0;
        $discountAmount = 0;
        $vatAmount = 0;

        foreach ($this->purchaseItems as $item) {
            $quantity = $item['quantity'] ?? 0;
            $rate = $item['rate'] ?? 0;
            $discount = $item['discount'] ?? 0;
            $tax = $item['tax_id'];

            $amount = $quantity * $rate;
            $discountTotal = $discount;
            if ($this->discountType == 'percentage') {
                $discountTotal = ($amount * $discount) / 100;
            }

            $taxAmount = 0;
            if ($tax) {
                // For simplicity, assume VAT is 15%
                $taxAmount = (($amount - $discountTotal) * 15) / 100;
            }

            $taxableAmount += ($amount - $discountTotal);
            $discountAmount += $discountTotal;
            $vatAmount += $taxAmount;
        }

        $this->totalTaxableAmount = $taxableAmount;
        $this->totalDiscountAmount = $discountAmount;
        $this->vatAmount = $vatAmount;

        // Calculate total amount
        $total = $taxableAmount + $vatAmount;

        // Apply round off if enabled
        if ($this->roundOff) {
            $rounded = round($total);
            $this->roundOffAmount = $rounded - $total;
            $this->totalAmount = $rounded;
        } else {
            $this->roundOffAmount = 0;
            $this->totalAmount = $total;
        }
    }

    public function updated($propertyName)
    {
        // Recalculate totals when relevant properties change
        if (in_array($propertyName, [
            'discountType', 'discountValue', 'roundOff', 'purchaseItems.*.quantity', 'purchaseItems.*.rate', 'purchaseItems.*.discount', 'purchaseItems.*.tax_id'
        ])) {
            $this->calculateTotals();
        }
    }

    public function savePurchase()
    {
        $this->validate();

        // Handle signature image upload
        $signaturePath = null;
        if ($this->signatureImage) {
            $signaturePath = $this->signatureImage->store('signatures', 'public');
        }

        // Save the purchase (assuming you have a Purchase model)
        // For demonstration, just a flash message
        // You should replace this with your actual saving logic

        // Example:
        // $purchase = Purchase::create([
        //     'purchase_id' => $this->purchaseId,
        //     'vendor_id' => $this->selectedVendor,
        //     'date' => $this->purchaseDate,
        //     'due_date' => $this->dueDate,
        //     'reference_no' => $this->referenceNo,
        //     'invoice_serial_no' => $this->invoiceSerialNo,
        //     'notes' => $this->notes,
        //     'terms_conditions' => $this->termsConditions,
        //     'signature_name' => $this->signatureName,
        //     'signature_image' => $signaturePath,
        //     'total_amount' => $this->totalAmount,
        //     // other fields...
        // ]);

        // Save items
        // foreach ($this->purchaseItems as $item) {
        //     // save each item
        // }

        session()->flash('message', 'Purchase saved successfully.');
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->purchaseId = '';
        $this->purchaseDate = '';
        $this->dueDate = '';
        $this->referenceNo = '';
        $this->invoiceSerialNo = '';
        $this->notes = '';
        $this->termsConditions = '';
        $this->signatureName = '';
        $this->signatureImage = null;
        $this->selectedVendor = null;
        $this->selectedProduct = null;
        $this->selectedTax = null;
        $this->selectedBank = null;
        $this->discountType = 'percentage';
        $this->discountValue = 0;
        $this->roundOff = false;
        $this->purchaseItems = [];
        $this->addItem(); // add a fresh item
        $this->calculateTotals();
    }

    public function render()
    {
        return view('livewire.add-purchases');
    }
}
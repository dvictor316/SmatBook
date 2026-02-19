<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvoiceDetails extends Component
{
    // Public property to hold the ID passed when mounting the component
    public $invoiceId;

    // Public property to hold the invoice data itself (optional, can be passed to render instead)
    public $invoice;

    public function mount($invoiceId)
    {
        $this->invoiceId = $invoiceId;
        // Optionally fetch the invoice here in mount() if needed across multiple methods
        // $this->loadInvoice(); 
    }

    // private function loadInvoice()
    // {
    //     try {
    //         $this->invoice = Invoice::with(['customer', 'items'])->findOrFail($this->invoiceId);
    //     } catch (ModelNotFoundException $e) {
    //         abort(404); // Handle the case where the invoice doesn't exist
    //     }
    // }

    public function render()
    {
        try {
            // Fetch the specific invoice details with customer and items eagerly loaded
            $invoice = Invoice::with(['customer', 'items'])->findOrFail($this->invoiceId);
            
            // Pass the data to the view
            return view('livewire.invoice-details', [
                'invoice' => $invoice,
                'currencySymbol' => $invoice->company->currency_symbol ?? '₦' // Fetch currency dynamically
            ]);

        } catch (ModelNotFoundException $e) {
            // If the invoice is not found, return a simple error view or abort
            return view('livewire.invoice-not-found'); 
        }
    }
}

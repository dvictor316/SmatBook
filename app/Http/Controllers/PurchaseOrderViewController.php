<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;

class PurchaseOrderViewController extends Controller
{
    public function showOrdersTable()
    {
        // Data is fetched into a temporary variable
        $data = Purchase::with(['supplier', 'items.product'])->get(); 

        // Pass the data to the view, but name the variable $transactions in the view scope
        return view('admin.purchase-order', ['transactions' => $data]);

        // NOTE: Using array syntax ['transactions' => $data] instead of compact() 
        // makes it clear we are renaming the variable for the view.
    }
}
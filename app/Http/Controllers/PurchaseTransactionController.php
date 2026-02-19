<?php

namespace App\Http\Controllers;

use App\Models\PurchaseTransaction;
use Illuminate\Http\Request;

class PurchaseTransactionController extends Controller
{
    public function index()
    {
        return response()->json(PurchaseTransaction::with('company')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id'       => 'required|exists:companies,id',
            'amount'           => 'required|numeric',
            'transaction_type' => 'required|string',
            'date'             => 'required|date',
        ]);

        $transaction = PurchaseTransaction::create($request->all());

        return response()->json($transaction, 201);
    }

    public function show(PurchaseTransaction $purchaseTransaction)
    {
        return response()->json($purchaseTransaction->load('company'));
    }

    public function update(Request $request, PurchaseTransaction $purchaseTransaction)
    {
        $purchaseTransaction->update($request->all());
        return response()->json($purchaseTransaction);
    }

    public function destroy(PurchaseTransaction $purchaseTransaction)
    {
        $purchaseTransaction->delete();
        return response()->json(['message' => 'Transaction deleted']);
    }
}

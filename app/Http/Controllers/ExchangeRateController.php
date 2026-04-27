<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExchangeRateController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $rates = ExchangeRate::forCompany($companyId)
            ->latest('effective_date')
            ->paginate(25);

        return view('exchange-rates.index', compact('rates'));
    }

    public function create()
    {
        return view('exchange-rates.create');
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $data = $request->validate([
            'base_currency'    => 'required|string|size:3',
            'target_currency'  => 'required|string|size:3|different:base_currency',
            'rate'             => 'required|numeric|min:0.000001',
            'effective_date'   => 'required|date',
            'source'           => 'nullable|string|max:100',
            'is_active'        => 'boolean',
        ]);

        $data['company_id']  = $companyId;
        $data['branch_id']   = Auth::user()->branch_id;
        $data['created_by']  = Auth::id();
        $data['is_active']   = $request->boolean('is_active', true);

        ExchangeRate::create($data);

        return redirect()->route('exchange-rates.index')
            ->with('success', 'Exchange rate saved.');
    }

    public function edit(ExchangeRate $exchangeRate)
    {
        $this->authorizeCompany($exchangeRate);
        return view('exchange-rates.edit', compact('exchangeRate'));
    }

    public function update(Request $request, ExchangeRate $exchangeRate)
    {
        $this->authorizeCompany($exchangeRate);

        $data = $request->validate([
            'rate'           => 'required|numeric|min:0.000001',
            'effective_date' => 'required|date',
            'source'         => 'nullable|string|max:100',
            'is_active'      => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $exchangeRate->update($data);

        return redirect()->route('exchange-rates.index')
            ->with('success', 'Exchange rate updated.');
    }

    public function destroy(ExchangeRate $exchangeRate)
    {
        $this->authorizeCompany($exchangeRate);
        $exchangeRate->delete();
        return back()->with('success', 'Exchange rate deleted.');
    }

    // API endpoint for live rate lookup
    public function getRate(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $from = strtoupper($request->input('from', ''));
        $to   = strtoupper($request->input('to', ''));

        if (! $from || ! $to) {
            return response()->json(['error' => 'from and to currencies required'], 422);
        }

        $rate = ExchangeRate::getRate($companyId, $from, $to);

        return response()->json([
            'from' => $from,
            'to'   => $to,
            'rate' => $rate,
        ]);
    }

    private function authorizeCompany(ExchangeRate $rate): void
    {
        abort_unless($rate->company_id === Auth::user()->company_id, 403);
    }
}

<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\Hotel\HotelDepositService;
use Illuminate\Http\Request;

class HotelDepositController extends Controller
{
    public function __construct(private readonly HotelDepositService $deposits) {}

    public function store(Request $request, Reservation $reservation)
    {
        $this->assertScope($reservation);
        $data = $request->validate(['deposit_amount' => 'required|numeric|min:0.01', 'payment_method' => 'required|in:cash,transfer,pos,card,other', 'account_id' => 'nullable|integer', 'reference' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:1000']);
        $this->deposits->receive($reservation, (float) $data['deposit_amount'], $data['payment_method'], $data['account_id'] ?? null, $data['reference'] ?? null, $data['notes'] ?? null);
        return back()->with('success', 'Reservation deposit received and posted to customer advances.');
    }

    public function refund(Request $request, Reservation $reservation)
    {
        $this->assertScope($reservation);
        $data = $request->validate(['deposit_amount' => 'required|numeric|min:0.01', 'account_id' => 'nullable|integer', 'reference' => 'required|string|max:100', 'notes' => 'required|string|max:1000']);
        $this->deposits->refund($reservation, (float) $data['deposit_amount'], $data['account_id'] ?? null, $data['reference'], $data['notes']);
        return back()->with('success', 'Reservation deposit refund posted with a balanced reversal.');
    }

    private function assertScope(Reservation $reservation): void
    {
        abort_unless((int) $reservation->company_id === (int) auth()->user()->company_id, 404);
        abort_if(in_array((string) $reservation->status, ['completed', 'no_show'], true), 422, 'Deposits cannot be changed for this reservation status.');
    }
}

<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Stay;
use App\Models\GuestFolio;
use Illuminate\Support\Facades\DB;

class WalkInController extends Controller
{
    public function create()
    {
        return view('hotel.walkin.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|exists:hotel_rooms,id',
            'customer_id' => 'nullable|exists:customers,id',
            'expected_checkout_at' => 'required|date|after:now'
        ]);

        DB::beginTransaction();
        try {
            $stay = Stay::create([
                'company_id' => auth()->user()->company_id,
                'property_id' => auth()->user()->branch_id,
                'customer_id' => $data['customer_id'] ?? null,
                'room_id' => $data['room_id'],
                'checkin_at' => now(),
                'expected_checkout_at' => $data['expected_checkout_at'],
                'status' => 'checked_in'
            ]);

            $folio = GuestFolio::create([
                'company_id' => auth()->user()->company_id,
                'property_id' => auth()->user()->branch_id,
                'stay_id' => $stay->id,
                'customer_id' => $data['customer_id'] ?? null,
                'folio_number' => 'FOLIO-'.now()->format('Ymd').'-'. $stay->id,
                'opening_deposit' => 0,
                'status' => 'open'
            ]);

            DB::commit();
            return redirect()->route('hotel.folios.show', $folio)->with('success','Walk-in created and checked-in');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

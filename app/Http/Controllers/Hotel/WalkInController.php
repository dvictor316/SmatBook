<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Stay;
use App\Models\GuestFolio;
use App\Models\HotelProperty;
use App\Models\HotelRoom;
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
            $propertyId = HotelProperty::where('company_id', auth()->user()->company_id)
                ->when(auth()->user()->branch_id, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                ->value('id');

            if (!$propertyId) {
                throw new \RuntimeException('No active hotel property found for current branch.');
            }

            $stay = Stay::create([
                'company_id' => auth()->user()->company_id,
                'property_id' => $propertyId,
                'customer_id' => $data['customer_id'] ?? null,
                'room_id' => $data['room_id'],
                'checkin_at' => now(),
                'expected_checkout_at' => $data['expected_checkout_at'],
                'status' => 'checked_in'
            ]);

            HotelRoom::where('id', $data['room_id'])->where('company_id', auth()->user()->company_id)->update([
                'operational_status' => 'occupied'
            ]);

            $folio = GuestFolio::create([
                'company_id' => auth()->user()->company_id,
                'property_id' => $propertyId,
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

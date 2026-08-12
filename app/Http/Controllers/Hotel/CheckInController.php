<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Stay;
use App\Models\GuestFolio;
use Illuminate\Support\Facades\DB;

class CheckInController extends Controller
{
    public function checkin(Reservation $reservation)
    {
        abort_unless($reservation->company_id == auth()->user()->company_id, 404);

        DB::beginTransaction();
        try {
            $stay = Stay::create([
                'company_id' => $reservation->company_id,
                'property_id' => $reservation->property_id,
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'room_id' => $reservation->room_id,
                'checkin_at' => now(),
                'expected_checkout_at' => $reservation->departure_date,
                'agreed_rate' => $reservation->nightly_rate,
                'status' => 'checked_in'
            ]);

            $folio = GuestFolio::create([
                'company_id' => $reservation->company_id,
                'property_id' => $reservation->property_id,
                'stay_id' => $stay->id,
                'reservation_id' => $reservation->id,
                'customer_id' => $reservation->customer_id,
                'folio_number' => 'FOLIO-'.now()->format('Ymd').'-'. $stay->id,
                'opening_deposit' => $reservation->deposit_received ?? 0,
                'status' => 'open'
            ]);

            $reservation->update(['status' => 'checked_in','checkin_id' => $stay->id]);

            DB::commit();
            return redirect()->route('hotel.folios.show', $folio)->with('success','Checked in successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function checkout(Stay $stay)
    {
        abort_unless($stay->company_id == auth()->user()->company_id, 404);

        DB::beginTransaction();
        try {
            // finalize folio and post accounting entries here (left for integration)
            $stay->update(['status' => 'checked_out', 'actual_checkout_at' => now()]);
            if ($stay->reservation) {
                $stay->reservation->update(['status' => 'completed','checkout_id' => $stay->id]);
            }
            DB::commit();
            return back()->with('success','Checked out');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

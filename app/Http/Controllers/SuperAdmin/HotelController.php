<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\HotelProperty;
use App\Models\HotelRoom;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        $totalHotelTenants = Company::whereRaw('LOWER(COALESCE(industry, "")) LIKE ?', ['%hotel%'])->count();

        $activeHotelSubscriptions = Subscription::whereNotNull('company_id')
            ->whereRaw('LOWER(payment_status) = ?', ['paid'])
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->when(true, fn($q) => $q->whereIn('company_id', function ($q2) {
                $q2->select('id')->from('companies')->whereRaw('LOWER(COALESCE(industry, "")) LIKE "\%hotel\%"');
            }))
            ->count();

        $totalProperties = HotelProperty::count();
        $totalRooms = HotelRoom::count();
        $availableRooms = HotelRoom::where('operational_status', 'available')->count();
        $occupiedRooms = HotelRoom::where('operational_status', 'occupied')->count();
        $reservedRooms = HotelRoom::where('operational_status', 'reserved')->count();

        $todayReservations = 0;
        if (Schema::hasTable('reservations')) {
            $today = now()->toDateString();
            $todayReservations = \DB::table('reservations')
                ->whereDate('arrival_date', '<=', $today)
                ->whereDate('departure_date', '>=', $today)
                ->count();
        }

        $currentInHouseGuests = 0;
        if (Schema::hasTable('stays')) {
            $currentInHouseGuests = \DB::table('stays')->where('status', 'checked_in')->count();
        }

        $hotelRevenueToday = 0;
        if (Schema::hasTable('hotel_transactions')) {
            $hotelRevenueToday = \DB::table('hotel_transactions')
                ->whereDate('created_at', now()->toDateString())
                ->sum('amount');
        }

        $hotelRevenueThisMonth = 0;
        if (Schema::hasTable('hotel_transactions')) {
            $hotelRevenueThisMonth = \DB::table('hotel_transactions')
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount');
        }

        $outstandingReceivables = 0;
        if (Schema::hasTable('guest_folios')) {
            $outstandingReceivables = \DB::table('guest_folios')->where('balance', '>', 0)->sum('balance');
        }

        return view('SuperAdmin.hotels.overview', compact(
            'totalHotelTenants', 'activeHotelSubscriptions', 'totalProperties', 'totalRooms', 'availableRooms', 'occupiedRooms', 'reservedRooms', 'todayReservations', 'currentInHouseGuests', 'hotelRevenueToday', 'hotelRevenueThisMonth', 'outstandingReceivables'
        ));
    }
}

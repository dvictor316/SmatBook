<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HotelProperty;
use Illuminate\Support\Facades\Auth;

class HotelSetupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function step1()
    {
        $company = Auth::user()->company;
        $property = HotelProperty::where('company_id', $company->id)->first();
        return view('hotel.setup.step1', compact('property'));
    }

    public function storeStep1(Request $request)
    {
        $company = Auth::user()->company;
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'currency_code' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:100',
            'default_checkin_time' => 'nullable',
            'default_checkout_time' => 'nullable',
        ]);

        $property = HotelProperty::updateOrCreate(
            ['company_id' => $company->id],
            array_merge($data, ['company_id' => $company->id])
        );

        return redirect()->route('hotel.setup.step2')->with('success', 'Property information saved.');
    }

    public function step2()
    {
        return view('hotel.setup.step2');
    }
}

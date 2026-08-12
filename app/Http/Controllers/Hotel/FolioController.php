<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GuestFolio;
use App\Models\FolioItem;

class FolioController extends Controller
{
    public function index()
    {
        $folios = GuestFolio::where('company_id', auth()->user()->company_id)
            ->when(auth()->user()->branch_id, function ($q) {
                $propertyId = \App\Models\HotelProperty::where('company_id', auth()->user()->company_id)
                    ->where('branch_id', auth()->user()->branch_id)
                    ->value('id');
                if ($propertyId) {
                    $q->where('property_id', $propertyId);
                }
            })
            ->latest('id')
            ->paginate(20);

        return view('hotel.folios.index', compact('folios'));
    }

    public function show(GuestFolio $folio)
    {
        abort_unless($folio->company_id == auth()->user()->company_id, 404);
        $items = FolioItem::where('folio_id', $folio->id)->latest('id')->get();
        return view('hotel.folios.show', compact('folio','items'));
    }

    public function storeItem(Request $request, GuestFolio $folio)
    {
        abort_unless($folio->company_id == auth()->user()->company_id, 404);

        $data = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01'
        ]);

        $item = FolioItem::create(array_merge($data, [
            'company_id' => $folio->company_id,
            'property_id' => $folio->property_id,
            'folio_id' => $folio->id,
            'type' => 'charge',
            'posted_by' => auth()->id()
        ]));

        // update folio totals (simplified)
        $folio->increment('total_charges', $item->amount);
        $folio->increment('balance', $item->amount);

        return back()->with('success','Item posted');
    }
}

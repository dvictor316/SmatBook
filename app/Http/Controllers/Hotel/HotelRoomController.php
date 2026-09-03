<?php
namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HotelRoom;
use App\Models\HotelRoomImage;
use App\Models\HotelRoomType;
use App\Models\HotelProperty;
use App\Models\Reservation;
use App\Models\Stay;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HotelRoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $propertyId = HotelProperty::where('company_id', $companyId)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->value('id');

        $status = trim((string) $request->query('status', ''));
        $viewMode = (string) $request->query('view', 'grid');

        $rooms = HotelRoom::with('type')
            ->with(['images', 'coverImage'])
            ->where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->when($status !== '', fn ($q) => $q->where(function ($sub) use ($status) {
                $sub->where('operational_status', $status)
                    ->orWhere('housekeeping_status', $status);
            }))
            ->paginate(30);

        $activeStays = Stay::query()
            ->with('customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('status', 'checked_in')
            ->whereIn('room_id', $rooms->pluck('id'))
            ->get()
            ->keyBy('room_id');

        $nextReservations = Reservation::query()
            ->with('customer')
            ->where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->whereIn('status', ['reserved', 'confirmed'])
            ->whereIn('room_id', $rooms->pluck('id'))
            ->whereDate('arrival_date', '>=', now()->toDateString())
            ->orderBy('arrival_date')
            ->get()
            ->groupBy('room_id')
            ->map(fn ($items) => $items->first());

        $summary = [
            'available' => HotelRoom::query()->where('company_id', $companyId)->when($propertyId, fn($q) => $q->where('property_id', $propertyId))->where('operational_status', 'available')->count(),
            'occupied' => HotelRoom::query()->where('company_id', $companyId)->when($propertyId, fn($q) => $q->where('property_id', $propertyId))->where('operational_status', 'occupied')->count(),
            'dirty' => HotelRoom::query()->where('company_id', $companyId)->when($propertyId, fn($q) => $q->where('property_id', $propertyId))->where('housekeeping_status', 'dirty')->count(),
            'maintenance' => HotelRoom::query()->where('company_id', $companyId)->when($propertyId, fn($q) => $q->where('property_id', $propertyId))->whereIn('operational_status', ['maintenance', 'out_of_order'])->count(),
        ];

        return view('hotel.rooms.index', compact('rooms', 'activeStays', 'nextReservations', 'summary', 'status', 'viewMode'));
    }

    public function create()
    {
        $companyId = Auth::user()->company_id;
        $propertyId = HotelProperty::where('company_id', $companyId)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->value('id');

        $roomTypes = HotelRoomType::where('company_id', $companyId)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->where('is_active', true)
            ->get();

        $properties = HotelProperty::where('company_id', $companyId)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->get();

        return view('hotel.rooms.create', compact('roomTypes', 'properties'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $data = $request->validate([
            'property_id' => 'required|exists:hotel_properties,id',
            'room_number' => 'required|string|max:50',
            'room_type_id' => 'nullable|exists:hotel_room_types,id',
            'floor' => 'nullable|string|max:50',
            'wing' => 'nullable|string|max:50',
            'base_rate_override' => 'nullable|numeric|min:0',
            'room_image' => 'nullable|image|max:5120',
            'panorama_image' => 'nullable|image|max:8192',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|image|max:8192',
            'operational_status' => 'nullable|string|max:50',
            'housekeeping_status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $data['company_id'] = $companyId;
        $data['room_image'] = $request->hasFile('room_image') ? $request->file('room_image')->store('hotel/rooms', 'public') : null;
        $data['panorama_image'] = $request->hasFile('panorama_image') ? $request->file('panorama_image')->store('hotel/rooms/panoramas', 'public') : null;

        // enforce uniqueness per property
        if (HotelRoom::where('property_id', $data['property_id'])->where('room_number', $data['room_number'])->exists()) {
            return back()->withErrors(['room_number' => 'Room number already exists for this property'])->withInput();
        }

        $room = HotelRoom::create($data);

        $this->syncLegacyMediaIntoGallery($room);
        $this->storeUploadedGalleryImages($request, $room);

        return redirect()->route('hotel.rooms.show', $room)->with('success', 'Room created.');
    }

    public function show(HotelRoom $room)
    {
        abort_unless($room->company_id === Auth::user()->company_id, 404);

        $room->load(['type', 'property', 'images']);

        $activeStay = Stay::query()
            ->with('customer')
            ->where('company_id', $room->company_id)
            ->where('room_id', $room->id)
            ->where('status', 'checked_in')
            ->latest('id')
            ->first();

        $upcomingReservations = Reservation::query()
            ->with('customer')
            ->where('company_id', $room->company_id)
            ->where('room_id', $room->id)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->whereDate('departure_date', '>=', now()->toDateString())
            ->orderBy('arrival_date')
            ->limit(8)
            ->get();

        $this->syncLegacyMediaIntoGallery($room);
        $room->refresh()->load(['type', 'property', 'images']);

        return view('hotel.rooms.show', compact('room', 'activeStay', 'upcomingReservations'));
    }

    public function edit(HotelRoom $room)
    {
        abort_unless($room->company_id === Auth::user()->company_id, 404);
        $companyId = Auth::user()->company_id;
        $roomTypes = HotelRoomType::where('company_id', $companyId)->where('is_active', true)->get();
        $room->load('images');
        return view('hotel.rooms.edit', compact('room', 'roomTypes'));
    }

    public function update(Request $request, HotelRoom $room)
    {
        abort_unless($room->company_id === Auth::user()->company_id, 404);
        $data = $request->validate([
            'room_type_id' => 'nullable|exists:hotel_room_types,id',
            'floor' => 'nullable|string|max:50',
            'wing' => 'nullable|string|max:50',
            'base_rate_override' => 'nullable|numeric|min:0',
            'room_image' => 'nullable|image|max:5120',
            'panorama_image' => 'nullable|image|max:8192',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable|image|max:8192',
            'operational_status' => 'nullable|string|max:50',
            'housekeeping_status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('room_image')) {
            if ($room->room_image) {
                Storage::disk('public')->delete($room->room_image);
            }
            $data['room_image'] = $request->file('room_image')->store('hotel/rooms', 'public');
        } else {
            unset($data['room_image']);
        }

        if ($request->hasFile('panorama_image')) {
            if ($room->panorama_image) {
                Storage::disk('public')->delete($room->panorama_image);
            }
            $data['panorama_image'] = $request->file('panorama_image')->store('hotel/rooms/panoramas', 'public');
        } else {
            unset($data['panorama_image']);
        }

        $room->update($data);
        $this->syncLegacyMediaIntoGallery($room->fresh());
        $this->storeUploadedGalleryImages($request, $room->fresh());

        return redirect()->route('hotel.rooms.show', $room)->with('success', 'Room updated.');
    }

    public function destroy(HotelRoom $room)
    {
        abort_unless($room->company_id === Auth::user()->company_id, 404);
        $room->is_active = false;
        $room->save();
        return redirect()->route('hotel.rooms.index')->with('success', 'Room deactivated.');
    }

    public function storeImages(Request $request, HotelRoom $room)
    {
        abort_unless($room->company_id === Auth::user()->company_id, 404);

        $request->validate([
            'gallery_images' => 'required|array|min:1',
            'gallery_images.*' => 'image|max:8192',
        ]);

        $this->storeUploadedGalleryImages($request, $room);

        return back()->with('success', 'Room images uploaded.');
    }

    public function reorderImages(Request $request, HotelRoom $room)
    {
        abort_unless($room->company_id === Auth::user()->company_id, 404);

        $data = $request->validate([
            'image_order' => 'required|array|min:1',
            'image_order.*' => 'integer',
            'cover_image_id' => 'nullable|integer',
            'panorama_image_id' => 'nullable|integer',
        ]);

        foreach ($data['image_order'] as $index => $imageId) {
            HotelRoomImage::query()
                ->where('company_id', $room->company_id)
                ->where('room_id', $room->id)
                ->where('id', (int) $imageId)
                ->update([
                    'sort_order' => $index + 1,
                    'is_cover' => (int) ($data['cover_image_id'] ?? 0) === (int) $imageId,
                    'is_panorama' => (int) ($data['panorama_image_id'] ?? 0) === (int) $imageId,
                ]);
        }

        $cover = HotelRoomImage::query()->where('room_id', $room->id)->where('is_cover', true)->first();
        $panorama = HotelRoomImage::query()->where('room_id', $room->id)->where('is_panorama', true)->first();

        $room->update([
            'room_image' => $cover?->path ?: $room->room_image,
            'panorama_image' => $panorama?->path ?: $room->panorama_image,
        ]);

        return back()->with('success', 'Room gallery order saved.');
    }

    public function destroyImage(HotelRoom $room, HotelRoomImage $image)
    {
        abort_unless($room->company_id === Auth::user()->company_id && (int) $image->room_id === (int) $room->id, 404);

        Storage::disk('public')->delete($image->path);

        if ($room->room_image === $image->path) {
            $room->room_image = null;
        }
        if ($room->panorama_image === $image->path) {
            $room->panorama_image = null;
        }
        $room->save();
        $image->delete();

        return back()->with('success', 'Room image deleted.');
    }

    private function storeUploadedGalleryImages(Request $request, HotelRoom $room): void
    {
        if (!$request->hasFile('gallery_images')) {
            return;
        }

        $nextSort = (int) HotelRoomImage::query()
            ->where('room_id', $room->id)
            ->max('sort_order');

        foreach ($request->file('gallery_images', []) as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $path = $file->store('hotel/rooms/gallery', 'public');
            $nextSort++;

            HotelRoomImage::create([
                'company_id' => $room->company_id,
                'property_id' => $room->property_id,
                'room_id' => $room->id,
                'path' => $path,
                'sort_order' => $nextSort,
                'is_cover' => !$room->room_image && $nextSort === 1,
                'uploaded_by' => Auth::id(),
            ]);
        }

        $firstImage = HotelRoomImage::query()->where('room_id', $room->id)->orderBy('sort_order')->first();
        if (!$room->room_image && $firstImage) {
            $room->update(['room_image' => $firstImage->path]);
        }
    }

    private function syncLegacyMediaIntoGallery(HotelRoom $room): void
    {
        foreach ([
            ['path' => $room->room_image, 'is_cover' => true, 'is_panorama' => false],
            ['path' => $room->panorama_image, 'is_cover' => false, 'is_panorama' => true],
        ] as $media) {
            if (!$media['path']) {
                continue;
            }

            HotelRoomImage::query()->firstOrCreate(
                ['room_id' => $room->id, 'path' => $media['path']],
                [
                    'company_id' => $room->company_id,
                    'property_id' => $room->property_id,
                    'caption' => $media['is_panorama'] ? 'Panorama preview' : 'Cover image',
                    'is_cover' => $media['is_cover'],
                    'is_panorama' => $media['is_panorama'],
                    'sort_order' => (int) HotelRoomImage::query()->where('room_id', $room->id)->max('sort_order') + 1,
                    'uploaded_by' => Auth::id(),
                ]
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\PartnerLocationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MapController extends Controller
{
    public function index()
    {
        $path = public_path('assets/json/inbox.json');
        
        // Use Laravel's File facade for cleaner syntax
        $rawData = File::exists($path) ? json_decode(File::get($path), true) : [];

        // Ensure we always have an array and add coordinates for the map
        $messages = collect($rawData)->map(function ($item) {
            return [
                'Name'          => $item['Name'] ?? 'Unknown',
                'Content'       => $item['Content'] ?? '',
                'Time'          => $item['Time'] ?? '',
                'Class'         => $item['Class'] ?? '',
                'StarClass'     => $item['StarClass'] ?? 'far fa-star',
                'HasAttachment' => $item['HasAttachment'] ?? false,
                // Assign consistent coordinates based on name or random for demo
                'lat'           => $item['lat'] ?? (float) ('0.' . substr(crc32($item['Name'] ?? 'a'), 0, 2)) * 100 - 20,
                'lng'           => $item['lng'] ?? (float) ('0.' . substr(crc32($item['Content'] ?? 'b'), 0, 2)) * 200 - 100,
            ];
        });

        return view('maps-vector', ['messages' => $messages]);
    }

    public function geoFinder(Request $request)
    {
        $companies = $this->accessibleCompanies();
        $selectedCompany = $this->resolveSelectedCompany($request, $companies);
        $category = $this->normalizeCategory((string) $request->query('category', 'business'));
        $radius = $this->normalizeRadius((int) $request->query('radius', 2000));
        $businessType = trim((string) $request->query('business_type', $this->categoryOptions()[$category] ?? 'business'));
        $searchTerm = $this->searchTermForCategory($category, $businessType);
        $country = trim((string) $request->query('country', 'Nigeria'));
        $state = trim((string) $request->query('state', 'FCT'));
        $localCouncil = trim((string) $request->query('local_council', ''));
        $center = $this->requestCoordinates($request)
            ?? $this->geocodeFreeformLocation($country, $state, $localCouncil)
            ?? ($selectedCompany ? $this->companyCoordinates($selectedCompany) : null);
        $nearbyResults = [];
        $lookupError = null;

        if ($request->boolean('search')) {
            if (!$center && $selectedCompany) {
                $geocoded = $this->geocodeCompanyAddress($selectedCompany);
                if ($geocoded) {
                    $selectedCompany = $selectedCompany->fresh() ?? $selectedCompany;
                    $center = $this->companyCoordinates($selectedCompany);
                }
            }

            if ($center) {
                [$nearbyResults, $lookupError] = $this->fetchNearbyPlaces($center['lat'], $center['lng'], $category, $radius, $searchTerm);

                if (empty($nearbyResults) && $radius < 10000) {
                    [$nearbyResults, $lookupError] = $this->fetchNearbyPlaces($center['lat'], $center['lng'], $category, 10000, $searchTerm);
                }

                if (empty($nearbyResults)) {
                    [$nearbyResults, $textError] = $this->searchPlacesByText($searchTerm, $country, $state, $localCouncil, $center['lat'], $center['lng']);
                    $lookupError = $nearbyResults ? null : ($textError ?: 'No matching places found. Try a broader business type or nearby council.');
                }
            } else {
                $lookupError = 'Choose a country/state/local council or allow device location before searching nearby places.';
            }
        }

        return view('deployment.geo-finder.index', [
            'companies' => $companies,
            'selectedCompany' => $selectedCompany,
            'center' => $center,
            'category' => $category,
            'radius' => $radius,
            'nearbyResults' => $nearbyResults,
            'lookupError' => $lookupError,
            'categoryOptions' => $this->categoryOptions(),
            'businessType' => $businessType,
            'country' => $country,
            'state' => $state,
            'localCouncil' => $localCouncil,
            'countryOptions' => PartnerLocationRepository::countryOptions(),
        ]);
    }

    public function geocodeCompany(Request $request)
    {
        $companies = $this->accessibleCompanies();
        $company = $this->resolveSelectedCompany($request, $companies);

        if (!$company) {
            return back()->with('error', 'Select a company to geocode.');
        }

        $coordinates = $this->geocodeCompanyAddress($company);

        if (!$coordinates) {
            return back()->with('error', 'Unable to geocode this company address. Please make the address more specific.');
        }

        return redirect()
            ->route('deployment.geo.index', ['company_id' => $company->id])
            ->with('success', 'Company location updated successfully.');
    }

    private function accessibleCompanies()
    {
        $user = Auth::user();
        $role = strtolower((string) ($user->role ?? ''));

        $query = Company::withoutGlobalScope('tenant')->orderBy('name');

        if (in_array($role, ['super_admin', 'superadmin', 'administrator', 'admin'], true)) {
            return $query->get();
        }

        if (in_array($role, ['state_manager', 'deployment_manager', 'manager'], true)) {
            $mappedIds = Schema::hasTable('deployment_companies')
                ? DB::table('deployment_companies')->where('manager_id', $user->id)->pluck('company_id')->all()
                : [];

            $legacyIds = Schema::hasColumn('companies', 'deployed_by')
                ? Company::withoutGlobalScope('tenant')->where('deployed_by', $user->id)->pluck('id')->all()
                : [];

            $ids = array_values(array_unique(array_merge($mappedIds, $legacyIds)));

            return $ids === []
                ? collect()
                : Company::withoutGlobalScope('tenant')->whereIn('id', $ids)->orderBy('name')->get();
        }

        $companyId = (int) ($user?->company_id ?? session('current_tenant_id') ?? 0);

        return $companyId > 0
            ? Company::withoutGlobalScope('tenant')->whereKey($companyId)->get()
            : collect();
    }

    private function resolveSelectedCompany(Request $request, $companies): ?Company
    {
        if ($companies->isEmpty()) {
            return null;
        }

        $companyId = (int) $request->input('company_id', $request->query('company_id', 0));

        if ($companyId > 0) {
            $company = $companies->firstWhere('id', $companyId);
            if ($company) {
                return $company;
            }
        }

        return $companies->first();
    }

    private function companyCoordinates(Company $company): ?array
    {
        if ($company->latitude === null || $company->longitude === null) {
            return null;
        }

        return [
            'lat' => (float) $company->latitude,
            'lng' => (float) $company->longitude,
            'label' => $company->location_label ?: ($company->address ?: $company->name),
        ];
    }

    private function geocodeCompanyAddress(Company $company): ?array
    {
        $address = trim(implode(', ', array_filter([
            $company->address,
            $company->name,
            $company->country,
        ])));

        if ($address === '') {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'SmartProbook Geo Finder/1.0',
            ])->timeout(12)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'jsonv2',
                'limit' => 1,
            ]);

            if (!$response->successful() || empty($response->json()[0])) {
                return null;
            }

            $match = $response->json()[0];
            $payload = array_filter([
                'latitude' => isset($match['lat']) ? (float) $match['lat'] : null,
                'longitude' => isset($match['lon']) ? (float) $match['lon'] : null,
                'location_label' => $match['display_name'] ?? $address,
                'geocoded_at' => now(),
            ], fn ($value) => $value !== null);

            if (!isset($payload['latitude'], $payload['longitude'])) {
                return null;
            }

            $company->forceFill($payload)->save();

            return [
                'lat' => (float) $payload['latitude'],
                'lng' => (float) $payload['longitude'],
                'label' => (string) ($payload['location_label'] ?? $address),
            ];
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function requestCoordinates(Request $request): ?array
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        return [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'label' => 'Current device location',
        ];
    }

    private function geocodeFreeformLocation(string $country, string $state, string $localCouncil = ''): ?array
    {
        $query = trim(implode(', ', array_filter([$localCouncil, $state, $country])));

        if ($query === '') {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'SmartProbook Geo Finder/1.0',
            ])->timeout(12)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => 1,
                'addressdetails' => 1,
            ]);

            $match = $response->successful() ? ($response->json()[0] ?? null) : null;

            if (!$match || !isset($match['lat'], $match['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $match['lat'],
                'lng' => (float) $match['lon'],
                'label' => $match['display_name'] ?? $query,
            ];
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function fetchNearbyPlaces(float $lat, float $lng, string $category, int $radius, string $businessType = ''): array
    {
        $filters = $this->overpassFilters($category);
        $queryParts = [];

        foreach ($filters as [$key, $value]) {
            $queryParts[] = sprintf('node[%s=%s](around:%d,%F,%F);', json_encode($key), json_encode($value), $radius, $lat, $lng);
            $queryParts[] = sprintf('way[%s=%s](around:%d,%F,%F);', json_encode($key), json_encode($value), $radius, $lat, $lng);
        }

        $query = '[out:json][timeout:18];(' . implode('', $queryParts) . ');out center tags 50;';

        try {
            $response = Http::timeout(24)->asForm()->post('https://overpass-api.de/api/interpreter', [
                'data' => $query,
            ]);

            if (!$response->successful()) {
                return [[], 'Nearby lookup is temporarily unavailable. Try again in a moment.'];
            }

            $elements = collect($response->json('elements') ?? []);
            $results = $elements
                ->map(function (array $element) use ($lat, $lng) {
                    $placeLat = $element['lat'] ?? ($element['center']['lat'] ?? null);
                    $placeLng = $element['lon'] ?? ($element['center']['lon'] ?? null);

                    if ($placeLat === null || $placeLng === null) {
                        return null;
                    }

                    $tags = $element['tags'] ?? [];
                    $name = $tags['name'] ?? $tags['brand'] ?? 'Unnamed place';

                    if ($businessType !== '' && $name !== 'Unnamed place') {
                        $haystack = strtolower($name . ' ' . implode(' ', array_filter([
                            $tags['shop'] ?? null,
                            $tags['amenity'] ?? null,
                            $tags['office'] ?? null,
                            $tags['tourism'] ?? null,
                            $tags['description'] ?? null,
                        ])));
                        $needle = strtolower($businessType);
                        if (!str_contains($haystack, $needle) && !str_contains($needle, strtolower((string) ($tags['shop'] ?? $tags['amenity'] ?? $tags['office'] ?? '')))) {
                            // Keep broad category results useful, but prioritize exact text matches elsewhere.
                        }
                    }

                    return [
                        'id' => ($element['type'] ?? 'node') . '-' . ($element['id'] ?? Str::uuid()),
                        'name' => $name,
                        'type' => $tags['shop'] ?? $tags['amenity'] ?? $tags['office'] ?? $tags['tourism'] ?? 'business',
                        'lat' => (float) $placeLat,
                        'lng' => (float) $placeLng,
                        'address' => $this->formatPlaceAddress($tags),
                        'phone' => $tags['phone'] ?? $tags['contact:phone'] ?? null,
                        'website' => $tags['website'] ?? $tags['contact:website'] ?? null,
                        'distance' => $this->distanceKm($lat, $lng, (float) $placeLat, (float) $placeLng),
                    ];
                })
                ->filter()
                ->sortBy('distance')
                ->take(50)
                ->values()
                ->all();

            return [$results, null];
        } catch (\Throwable $e) {
            report($e);
            return [[], 'Nearby lookup could not connect right now. The saved company location is still available.'];
        }
    }

    private function searchPlacesByText(string $businessType, string $country, string $state, string $localCouncil, float $lat, float $lng): array
    {
        $queries = collect([
            trim(implode(' ', array_filter([$businessType ?: 'business', $localCouncil, $state, $country]))),
            trim(implode(' ', array_filter([$businessType ?: 'business', $state, $country]))),
            trim(implode(' ', array_filter(['shop', $localCouncil, $state, $country]))),
            trim(implode(' ', array_filter(['business', $state, $country]))),
        ])->filter()->unique()->values();

        try {
            $places = collect();

            foreach ($queries as $query) {
                $response = Http::withHeaders([
                    'User-Agent' => 'SmartProbook Geo Finder/1.0',
                ])->timeout(12)->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 32,
                    'addressdetails' => 1,
                    'extratags' => 1,
                ]);

                if (!$response->successful()) {
                    continue;
                }

                $places = $places->merge($response->json() ?? []);

                if ($places->count() >= 8) {
                    break;
                }
            }

            if ($places->isEmpty()) {
                return [[], 'Map search is temporarily unavailable or returned no indexed places. Try using device location and a broader store type.'];
            }

            $results = $places
                ->filter(fn ($place) => isset($place['lat'], $place['lon']))
                ->unique(fn ($place) => (string) ($place['place_id'] ?? (($place['lat'] ?? '') . ',' . ($place['lon'] ?? ''))))
                ->map(function (array $place) use ($lat, $lng, $businessType) {
                    $extra = $place['extratags'] ?? [];
                    $address = $place['address'] ?? [];
                    $name = $place['name'] ?? $place['display_name'] ?? 'Nearby Business';

                    return [
                        'id' => 'nominatim-' . ($place['place_id'] ?? Str::uuid()),
                        'name' => Str::limit((string) $name, 80, ''),
                        'type' => $place['type'] ?? $businessType ?: 'business',
                        'lat' => (float) $place['lat'],
                        'lng' => (float) $place['lon'],
                        'address' => $place['display_name'] ?? implode(', ', array_filter($address)),
                        'phone' => $extra['phone'] ?? $extra['contact:phone'] ?? null,
                        'website' => $extra['website'] ?? $extra['contact:website'] ?? null,
                        'distance' => $this->distanceKm($lat, $lng, (float) $place['lat'], (float) $place['lon']),
                    ];
                })
                ->sortBy('distance')
                ->values()
                ->all();

            return [$results, null];
        } catch (\Throwable $e) {
            report($e);
            return [[], 'Map search could not connect right now. Try again in a moment.'];
        }
    }

    private function categoryOptions(): array
    {
        return [
            'business' => 'Businesses',
            'store' => 'Stores',
            'supermarket' => 'Supermarkets',
            'pharmacy' => 'Pharmacies',
            'hospital' => 'Hospitals',
            'restaurant' => 'Restaurants',
            'bank' => 'Banks',
            'fuel' => 'Fuel Stations',
            'school' => 'Schools',
            'hotel' => 'Hotels',
        ];
    }

    private function normalizeCategory(string $category): string
    {
        return array_key_exists($category, $this->categoryOptions()) ? $category : 'business';
    }

    private function searchTermForCategory(string $category, string $businessType): string
    {
        $typed = strtolower(trim($businessType));

        if ($typed !== '' && !in_array($typed, array_map('strtolower', $this->categoryOptions()), true)) {
            return $businessType;
        }

        return match ($category) {
            'store' => 'shop',
            'supermarket' => 'supermarket',
            'pharmacy' => 'pharmacy',
            'hospital' => 'hospital clinic',
            'restaurant' => 'restaurant',
            'bank' => 'bank',
            'fuel' => 'fuel station',
            'school' => 'school',
            'hotel' => 'hotel',
            default => 'business',
        };
    }

    private function normalizeRadius(int $radius): int
    {
        return max(250, min($radius ?: 2000, 10000));
    }

    private function overpassFilters(string $category): array
    {
        return match ($category) {
            'store' => [['shop', 'convenience'], ['shop', 'general'], ['shop', 'department_store'], ['shop', 'mall']],
            'supermarket' => [['shop', 'supermarket']],
            'pharmacy' => [['amenity', 'pharmacy'], ['shop', 'chemist']],
            'hospital' => [['amenity', 'hospital'], ['amenity', 'clinic'], ['amenity', 'doctors']],
            'restaurant' => [['amenity', 'restaurant'], ['amenity', 'fast_food'], ['amenity', 'cafe']],
            'bank' => [['amenity', 'bank'], ['amenity', 'atm']],
            'fuel' => [['amenity', 'fuel']],
            'school' => [['amenity', 'school'], ['amenity', 'college'], ['amenity', 'university']],
            'hotel' => [['tourism', 'hotel'], ['tourism', 'guest_house']],
            default => [['shop', 'supermarket'], ['shop', 'convenience'], ['office', 'company'], ['amenity', 'bank'], ['amenity', 'restaurant']],
        };
    }

    private function formatPlaceAddress(array $tags): string
    {
        $parts = array_filter([
            $tags['addr:housenumber'] ?? null,
            $tags['addr:street'] ?? null,
            $tags['addr:suburb'] ?? null,
            $tags['addr:city'] ?? null,
        ]);

        return $parts === [] ? 'No address listed' : implode(', ', $parts);
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}

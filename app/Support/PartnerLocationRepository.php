<?php

namespace App\Support;

class PartnerLocationRepository
{
    private const GENERATED_PATH = 'resources/data/partner_locations.generated.php';

    public static function countries(): array
    {
        return array_keys(self::regions());
    }

    public static function countryOptions(): array
    {
        $countries = self::countries();

        return array_combine($countries, $countries) ?: [];
    }

    public static function regions(): array
    {
        static $regions;

        if ($regions !== null) {
            return $regions;
        }

        $generatedPath = base_path(self::GENERATED_PATH);
        $generated = file_exists($generatedPath) ? require $generatedPath : [];
        $fallback = config('partner_locations.regions', []);

        // Keep the curated Nigeria LGA map, which is more specific than the city dataset.
        if (isset($fallback['Nigeria']) && is_array($fallback['Nigeria'])) {
            $generated['Nigeria'] = $fallback['Nigeria'];
        }

        $regions = is_array($generated) && $generated !== [] ? $generated : $fallback;

        return $regions;
    }

    public static function statesForCountry(?string $country): array
    {
        $country = trim((string) $country);

        if ($country === '') {
            return [];
        }

        return array_keys(self::regions()[$country] ?? []);
    }

    public static function councilsForCountryState(?string $country, ?string $state): array
    {
        $country = trim((string) $country);
        $state = trim((string) $state);

        if ($country === '' || $state === '') {
            return [];
        }

        return array_values(self::regions()[$country][$state] ?? []);
    }

    public static function hasCountry(?string $country): bool
    {
        $country = trim((string) $country);

        return $country !== '' && array_key_exists($country, self::regions());
    }

    public static function hasState(?string $country, ?string $state): bool
    {
        $country = trim((string) $country);
        $state = trim((string) $state);

        return $country !== ''
            && $state !== ''
            && array_key_exists($state, self::regions()[$country] ?? []);
    }

    public static function hasCouncil(?string $country, ?string $state, ?string $council): bool
    {
        $council = trim((string) $council);

        if ($council === '') {
            return true;
        }

        return in_array($council, self::councilsForCountryState($country, $state), true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = ['key', 'value'];

    public const BUSINESS_PROFILE_FIELDS = [
        'company_name', 'company_phone', 'company_email', 'address_line_1', 'address_line_2',
        'company_city', 'company_state', 'company_country', 'company_pincode', 'receipt_footer',
        'site_logo', 'favicon', 'company_icon', 'invoice_logo', 'digital_signature',
    ];

    public static function businessProfile(): array
    {
        $user = auth()->user();
        $resolver = app(\App\Support\BusinessWorkspaceResolver::class);
        $companyId = $user ? $resolver->activeCompanyId($user) : 0;
        $profile = array_fill_keys(self::BUSINESS_PROFILE_FIELDS, null);
        if ($companyId <= 0) {
            return $profile;
        }

        $company = Company::withoutGlobalScope('tenant')->find($companyId);
        $profile = array_replace($profile, [
            'company_name' => $company?->name ?: $company?->company_name,
            'company_phone' => $company?->phone,
            'company_email' => $company?->email,
            'address_line_1' => $company?->address,
            'company_country' => $company?->country,
            'site_logo' => $company?->logo,
        ]);
        $keys = array_map(fn ($field) => $field.'_company_'.$companyId, self::BUSINESS_PROFILE_FIELDS);
        $values = self::whereIn('key', $keys)->pluck('value', 'key');
        foreach (self::BUSINESS_PROFILE_FIELDS as $field) {
            if ($values->has($field.'_company_'.$companyId)) {
                $profile[$field] = $values[$field.'_company_'.$companyId];
            }
        }

        return $profile;
    }

    /**
     * Helper to get a setting value instantly in Blade
     * Usage: {{ \App\Models\Setting::get('first_name') }}
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function getSensitive($key, $default = null)
    {
        $value = (string) self::get($key, '');
        if ($value === '') {
            return $default;
        }

        if (str_starts_with($value, 'enc:')) {
            try {
                return Crypt::decryptString(substr($value, 4));
            } catch (\Throwable $e) {
                return $default;
            }
        }

        // Backward compatibility for old plain-text records.
        return $value;
    }

    public static function putSensitive(string $key, ?string $value): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            return;
        }

        self::updateOrCreate(
            ['key' => $key],
            ['value' => 'enc:' . Crypt::encryptString($value)]
        );
    }

    public static function mediaUrl(?string $path, ?string $fallback = null): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return $fallback;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        if (Storage::disk('public')->exists(ltrim($path, '/'))) {
            return Storage::disk('public')->url(ltrim($path, '/'));
        }

        return asset(ltrim($path, '/'));
    }

    public static function mailFromName(?string $fallback = null): string
    {
        $candidates = [
            (string) self::get('mail_from_name', ''),
            (string) config('mail.from.name', ''),
            (string) (self::businessProfile()['company_name'] ?? ''),
            (string) env('MAIL_FROM_NAME', ''),
            (string) config('app.name', ''),
            (string) ($fallback ?? ''),
            'SmartProbook',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'SmartProbook';
    }

    public static function mailFromAddress(?string $fallback = null): string
    {
        $smtpUsername = trim((string) self::getSensitive('mail_smtp_username', self::get('mail_smtp_username', '')));
        if ($smtpUsername === '') {
            $smtpUsername = trim((string) self::get('mail_smtp_username', self::get('mail_username', '')));
        }

        $candidates = [
            (string) self::get('mail_from_address', ''),
            $smtpUsername,
            (string) (self::businessProfile()['company_email'] ?? ''),
            (string) config('mail.from.address', ''),
            (string) env('MAIL_FROM_ADDRESS', ''),
            (string) ($fallback ?? ''),
            'smartprobookoffice@gmail.com',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return 'smartprobookoffice@gmail.com';
    }
}

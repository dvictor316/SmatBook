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
            (string) self::get('company_name', ''),
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
        $candidates = [
            (string) self::get('mail_from_address', ''),
            (string) self::get('company_email', ''),
            (string) config('mail.from.address', ''),
            (string) env('MAIL_FROM_ADDRESS', ''),
            (string) ($fallback ?? ''),
            'support@smartprobook.com',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return 'support@smartprobook.com';
    }
}

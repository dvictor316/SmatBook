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
}

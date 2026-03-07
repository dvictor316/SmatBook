<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

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
}

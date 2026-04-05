<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class SafeRoute
{
    public static function to(string $name, string $fallback, array $parameters = []): string
    {
        try {
            if (Route::has($name)) {
                return route($name, $parameters);
            }
        } catch (\Throwable $e) {
            // Fall through to fallback.
        }

        return url($fallback);
    }
}

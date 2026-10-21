<?php

namespace App\Support;

class PageSize
{
    public const OPTIONS = [25, 50, 100, 250, 500];
    public const DEFAULT = 500;

    public static function resolve(): int
    {
        $value = request()->query('per_page', self::DEFAULT);
        if (! is_scalar($value) || ! preg_match('/^[1-9][0-9]*$/', (string) $value)) {
            return self::DEFAULT;
        }
        return min(self::DEFAULT, (int) $value);
    }
}

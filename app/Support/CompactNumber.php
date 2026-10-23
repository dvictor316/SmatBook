<?php

namespace App\Support;

class CompactNumber
{
    public static function format(
        float|int|string|null $number,
        int $decimals = 0,
        string $decimalSeparator = '.',
        string $thousandsSeparator = ','
    ): string {
        $formatted = number_format((float) ($number ?? 0), $decimals, $decimalSeparator, $thousandsSeparator);

        return self::trimZeroFraction($formatted, $decimalSeparator, $decimals);
    }

    public static function trimZeroFraction(string $formatted, string $decimalSeparator = '.', int $decimals = 2): string
    {
        if ($decimals !== 2) {
            return $formatted;
        }

        $zeroFraction = $decimalSeparator.'00';

        return str_ends_with($formatted, $zeroFraction)
            ? substr($formatted, 0, -strlen($zeroFraction))
            : $formatted;
    }
}

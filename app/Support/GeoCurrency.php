<?php

namespace App\Support;

class GeoCurrency
{
    /**
     * Static NGN base rates fallback for safe, deterministic rendering.
     * These are display rates only and do not alter stored transaction values.
     */
    private const NGN_RATES = [
        'NGN' => 1.0,
        'USD' => 0.00067,
        'CNY' => 0.0048,
        'GBP' => 0.00053,
        'EUR' => 0.00062,
        'CAD' => 0.00091,
        'INR' => 0.056,
        'AED' => 0.00246,
        'ZAR' => 0.0125,
        'KES' => 0.086,
        'GHS' => 0.0105,
    ];

    private const COUNTRY_TO_CURRENCY = [
        'NG' => ['currency' => 'NGN', 'locale' => 'en-NG'],
        'US' => ['currency' => 'USD', 'locale' => 'en-US'],
        'CN' => ['currency' => 'CNY', 'locale' => 'zh-CN'],
        'GB' => ['currency' => 'GBP', 'locale' => 'en-GB'],
        'EU' => ['currency' => 'EUR', 'locale' => 'en-IE'],
        'CA' => ['currency' => 'CAD', 'locale' => 'en-CA'],
        'IN' => ['currency' => 'INR', 'locale' => 'en-IN'],
        'AE' => ['currency' => 'AED', 'locale' => 'en-AE'],
        'ZA' => ['currency' => 'ZAR', 'locale' => 'en-ZA'],
        'KE' => ['currency' => 'KES', 'locale' => 'en-KE'],
        'GH' => ['currency' => 'GHS', 'locale' => 'en-GH'],
    ];

    private const EU_REGIONS = [
        'FR', 'DE', 'ES', 'IT', 'PT', 'NL', 'BE', 'AT', 'IE', 'FI', 'SE', 'DK', 'PL', 'CZ', 'GR', 'RO', 'HU',
    ];

    public static function currentCountry(?string $default = 'NG'): string
    {
        $request = request();
        $cookieCode = strtoupper((string) ($request?->cookie('smat_country') ?? ''));

        if ($cookieCode !== '') {
            return self::normalizeCountry($cookieCode, $default ?? 'NG');
        }

        $locale = (string) app()->getLocale();
        $region = strtoupper((string) (str_contains($locale, '-') ? explode('-', $locale)[1] : ''));
        if ($region !== '') {
            return self::normalizeCountry($region, $default ?? 'NG');
        }

        return $default ?? 'NG';
    }

    public static function currentCurrency(): string
    {
        $country = self::currentCountry('NG');
        return self::COUNTRY_TO_CURRENCY[$country]['currency'] ?? 'NGN';
    }

    public static function currentLocale(): string
    {
        $country = self::currentCountry('NG');
        return self::COUNTRY_TO_CURRENCY[$country]['locale'] ?? 'en-NG';
    }

    public static function format(float|int|string|null $amount, string $sourceCurrency = 'NGN', ?string $targetCurrency = null, ?string $locale = null): string
    {
        $numericAmount = (float) ($amount ?? 0);
        $target = strtoupper($targetCurrency ?: self::currentCurrency());
        $displayLocale = $locale ?: self::currentLocale();
        $converted = self::convert($numericAmount, $sourceCurrency, $target);

        if (!class_exists(\NumberFormatter::class)) {
            return $target . ' ' . number_format($converted, 2);
        }

        $formatter = new \NumberFormatter($displayLocale, \NumberFormatter::CURRENCY);
        $result = $formatter->formatCurrency($converted, $target);

        if ($result === false) {
            return $target . ' ' . number_format($converted, 2);
        }

        return $result;
    }

    public static function convert(float|int|string|null $amount, string $sourceCurrency = 'NGN', ?string $targetCurrency = null): float
    {
        $numericAmount = (float) ($amount ?? 0);
        $source = self::normalizeCurrency($sourceCurrency);
        $target = self::normalizeCurrency($targetCurrency ?: self::currentCurrency());

        if ($source === $target) {
            return $numericAmount;
        }

        $sourceRate = self::NGN_RATES[$source] ?? null;
        $targetRate = self::NGN_RATES[$target] ?? null;

        if (!$sourceRate || !$targetRate) {
            return $numericAmount;
        }

        // Convert source amount to NGN baseline then to target currency.
        $amountInNgn = $source === 'NGN' ? $numericAmount : ($numericAmount / $sourceRate);
        return $target === 'NGN' ? $amountInNgn : ($amountInNgn * $targetRate);
    }

    public static function normalizeCurrency(string $currency): string
    {
        $value = strtoupper(trim($currency));

        return match ($value) {
            '$', 'USD' => 'USD',
            '₦', 'NGN' => 'NGN',
            '¥', 'CNY' => 'CNY',
            '£', 'GBP' => 'GBP',
            '€', 'EUR' => 'EUR',
            default => $value !== '' ? $value : 'NGN',
        };
    }

    public static function normalizeCountry(string $country, string $fallback = 'NG'): string
    {
        $code = strtoupper(trim($country));

        if (in_array($code, self::EU_REGIONS, true)) {
            return 'EU';
        }

        return array_key_exists($code, self::COUNTRY_TO_CURRENCY) ? $code : strtoupper($fallback);
    }
}

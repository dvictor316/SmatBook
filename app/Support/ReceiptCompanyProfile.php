<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Setting;

class ReceiptCompanyProfile
{
    private const ADDRESS_FIELDS = [
        'address_line_1',
        'address_line_2',
        'company_city',
        'company_state',
        'company_pincode',
        'company_country',
    ];

    public static function address(?Company $company = null): string
    {
        $companyId = (int) ($company?->id ?? auth()->user()?->company_id ?? 0);
        if ($companyId > 0) {
            $scopedKeys = array_map(
                fn (string $field) => "{$field}_company_{$companyId}",
                self::ADDRESS_FIELDS
            );
            $scopedValues = Setting::query()->whereIn('key', $scopedKeys)->pluck('value', 'key');

            if ($scopedValues->isNotEmpty()) {
                return self::formatAddress(self::ADDRESS_FIELDS, $scopedValues->all(), $companyId);
            }
        }

        $legacyValues = Setting::query()
            ->whereIn('key', self::ADDRESS_FIELDS)
            ->pluck('value', 'key');
        if ($legacyValues->isNotEmpty()) {
            return self::formatAddress(self::ADDRESS_FIELDS, $legacyValues->all());
        }

        return trim((string) ($company?->address ?: Setting::get('company_address', '')));
    }

    public static function footer(?Company $company = null): string
    {
        $companyId = (int) ($company?->id ?? auth()->user()?->company_id ?? 0);
        $footer = $companyId > 0
            ? Setting::where('key', "receipt_footer_company_{$companyId}")->value('value')
            : null;
        $footer ??= Setting::get('receipt_footer');

        return trim((string) $footer) ?: 'Thank you for your patronage.';
    }

    private static function formatAddress(array $fields, array $values, ?int $companyId = null): string
    {
        $parts = [];
        foreach ($fields as $field) {
            $key = $companyId ? "{$field}_company_{$companyId}" : $field;
            $value = trim((string) ($values[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return implode(', ', $parts);
    }
}

<?php

namespace App\Support;

use App\Models\TaxJurisdiction;

class TaxFilingCatalog
{
    private const TYPE_LABELS = [
        'vat' => 'Value Added Tax (VAT) Return',
        'sales_tax' => 'Sales and Use Tax Return',
        'withholding' => 'Withholding Tax Return',
        'paye' => 'PAYE / Payroll Tax Return',
        'corporate_income_tax' => 'Company Income Tax Return',
    ];

    private const FREQUENCY_LABELS = [
        'weekly' => 'Weekly',
        'biweekly' => 'Every Two Weeks',
        'monthly' => 'Monthly',
        'bimonthly' => 'Every Two Months',
        'quarterly' => 'Quarterly',
        'semiannual' => 'Twice a Year',
        'annual' => 'Annual',
    ];

    public function optionsFor(TaxJurisdiction $jurisdiction): array
    {
        $jurisdiction->loadMissing(['taxCodes', 'withholdingRules', 'accountMappings']);
        $activeCodes = $jurisdiction->taxCodes->where('is_active', true);
        $options = [];

        foreach (['vat', 'sales_tax', 'corporate_income_tax'] as $type) {
            $codes = $activeCodes->where('type', $type);
            if ($codes->isEmpty()) {
                continue;
            }

            $frequencies = $codes->pluck('filing_frequency')
                ->filter()
                ->map(fn ($frequency) => $this->normalizeFrequency((string) $frequency))
                ->unique()
                ->values()
                ->all();
            $options[$type] = $this->option(
                $jurisdiction,
                $type,
                $frequencies,
                (int) ($codes->pluck('filing_deadline_days')->filter()->first() ?? 0)
            );
        }

        if ($jurisdiction->withholdingRules->where('is_active', true)->isNotEmpty()) {
            $options['withholding'] = $this->option($jurisdiction, 'withholding');
        }

        $isFederalNigeria = strtoupper((string) $jurisdiction->country_code) === 'NGA'
            && str_contains(strtolower((string) $jurisdiction->region), 'federal');
        $hasPayeMapping = $jurisdiction->accountMappings->contains('tax_type', 'paye');
        if ($hasPayeMapping && ! $isFederalNigeria) {
            $options['paye'] = $this->option($jurisdiction, 'paye', ['monthly']);
        }

        return $options;
    }

    public function frequencyLabels(): array
    {
        return self::FREQUENCY_LABELS;
    }

    private function option(
        TaxJurisdiction $jurisdiction,
        string $type,
        array $frequencies = [],
        int $deadlineDays = 0
    ): array {
        $isNigeriaCompanyTax = strtoupper((string) $jurisdiction->country_code) === 'NGA'
            && $type === 'corporate_income_tax';
        $defaultFrequency = $this->normalizeFrequency((string) $jurisdiction->filing_frequency);
        $frequencies = array_values(array_unique(array_filter($frequencies ?: [$defaultFrequency])));
        $defaultFrequency = in_array($defaultFrequency, $frequencies, true)
            ? $defaultFrequency
            : ($frequencies[0] ?? 'annual');

        return [
            'value' => $type,
            'label' => self::TYPE_LABELS[$type],
            'frequencies' => $frequencies,
            'default_frequency' => $defaultFrequency,
            'deadline_days' => $isNigeriaCompanyTax
                ? 0
                : ($deadlineDays ?: (int) $jurisdiction->filing_deadline_days),
            'deadline_months' => $isNigeriaCompanyTax ? 6 : 0,
            'authority' => $jurisdiction->tax_authority_name ?: $jurisdiction->name,
            'currency' => strtoupper((string) ($jurisdiction->currency_code ?: 'NGN')),
            'due_rule' => data_get($jurisdiction->metadata, 'due_rule'),
            'requires_review' => $type === 'sales_tax'
                && (bool) data_get($jurisdiction->metadata, 'requires_nexus_review', false),
        ];
    }

    private function normalizeFrequency(string $frequency): string
    {
        return match (strtolower(trim($frequency))) {
            'annually', 'yearly' => 'annual',
            'semi-annually', 'semiannually' => 'semiannual',
            default => strtolower(trim($frequency)),
        };
    }
}

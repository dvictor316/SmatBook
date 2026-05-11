<?php

namespace App\Support;

use Illuminate\Support\Collection;

class TaxComputationService
{
    public function computeBreakdown(float $amount, iterable $taxCodes, array $options = []): array
    {
        $taxableAmount = max(0, round($amount, 4));
        $currency = (string) ($options['currency_code'] ?? 'NGN');
        $taxCodes = collect($taxCodes)
            ->filter(fn ($taxCode) => (bool) ($taxCode['is_active'] ?? $taxCode->is_active ?? true))
            ->sortBy(fn ($taxCode) => (int) ($taxCode['compound_order'] ?? $taxCode->compound_order ?? 0))
            ->values();

        $lines = [];
        $runningBase = $taxableAmount;
        $runningGross = $taxableAmount;
        $totalTax = 0.0;
        $recoverableTax = 0.0;
        $nonRecoverableTax = 0.0;

        foreach ($taxCodes as $taxCode) {
            $line = $this->calculateTaxLine($runningGross, $taxCode, $options + ['base_amount' => $runningBase]);
            $lines[] = $line;

            $totalTax += $line['tax_amount'];
            $recoverableTax += $line['recoverable_amount'];
            $nonRecoverableTax += $line['non_recoverable_amount'];

            if ($line['is_compound']) {
                $runningGross += $line['tax_amount'];
            }
        }

        return [
            'currency_code' => $currency,
            'taxable_amount' => round($taxableAmount, 2),
            'net_amount' => round($taxableAmount, 2),
            'gross_amount' => round($taxableAmount + $totalTax, 2),
            'total_tax' => round($totalTax, 2),
            'recoverable_tax' => round($recoverableTax, 2),
            'non_recoverable_tax' => round($nonRecoverableTax, 2),
            'lines' => $lines,
        ];
    }

    public function calculateTaxLine(float $amount, $taxCode, array $options = []): array
    {
        $rate = max(0, (float) ($taxCode['rate'] ?? $taxCode->rate ?? 0));
        $type = (string) ($taxCode['type'] ?? $taxCode->type ?? 'vat');
        $name = (string) ($taxCode['name'] ?? $taxCode->name ?? $taxCode['description'] ?? $taxCode->description ?? $type);
        $calculationMethod = strtolower((string) ($taxCode['calculation_method'] ?? $taxCode->calculation_method ?? 'exclusive'));
        $isCompound = (bool) ($taxCode['is_compound'] ?? $taxCode->is_compound ?? false);
        $isZeroRated = (bool) ($taxCode['is_zero_rated'] ?? $taxCode->is_zero_rated ?? false);
        $isExempt = (bool) ($taxCode['is_exempt'] ?? $taxCode->is_exempt ?? false);
        $recoverabilityRate = max(0, min(100, (float) ($taxCode['recoverability_rate'] ?? $taxCode->recoverability_rate ?? 100)));
        $baseAmount = max(0, (float) ($options['base_amount'] ?? $amount));

        if ($isZeroRated || $isExempt || $rate <= 0) {
            return [
                'tax_code' => (string) ($taxCode['code'] ?? $taxCode->code ?? $name),
                'tax_name' => $name,
                'tax_type' => $type,
                'rate' => round($rate, 4),
                'base_amount' => round($baseAmount, 2),
                'tax_amount' => 0.0,
                'recoverable_amount' => 0.0,
                'non_recoverable_amount' => 0.0,
                'calculation_method' => $calculationMethod,
                'is_compound' => $isCompound,
                'is_zero_rated' => $isZeroRated,
                'is_exempt' => $isExempt,
                'supports_reverse_charge' => (bool) ($taxCode['supports_reverse_charge'] ?? $taxCode->supports_reverse_charge ?? false),
            ];
        }

        $taxAmount = 0.0;
        if ($calculationMethod === 'inclusive') {
            $netBase = $amount / (1 + ($rate / 100));
            $taxAmount = $amount - $netBase;
            $baseAmount = $netBase;
        } else {
            $taxAmount = $baseAmount * ($rate / 100);
        }

        $recoverableAmount = $taxAmount * ($recoverabilityRate / 100);
        $nonRecoverableAmount = $taxAmount - $recoverableAmount;

        return [
            'tax_code' => (string) ($taxCode['code'] ?? $taxCode->code ?? $name),
            'tax_name' => $name,
            'tax_type' => $type,
            'rate' => round($rate, 4),
            'base_amount' => round($baseAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'recoverable_amount' => round($recoverableAmount, 2),
            'non_recoverable_amount' => round($nonRecoverableAmount, 2),
            'calculation_method' => $calculationMethod,
            'is_compound' => $isCompound,
            'is_zero_rated' => $isZeroRated,
            'is_exempt' => $isExempt,
            'supports_reverse_charge' => (bool) ($taxCode['supports_reverse_charge'] ?? $taxCode->supports_reverse_charge ?? false),
        ];
    }

    public function calculateWithholding(float $amount, $rule, array $options = []): array
    {
        $threshold = max(0, (float) ($rule['threshold_amount'] ?? $rule->threshold_amount ?? 0));
        $rate = max(0, (float) ($rule['rate'] ?? $rule->rate ?? 0));
        $serviceType = (string) ($rule['service_type'] ?? $rule->service_type ?? $rule['name'] ?? $rule->name ?? 'withholding');
        $counterpartyType = (string) ($rule['counterparty_type'] ?? $rule->counterparty_type ?? 'vendor');
        $baseAmount = max(0, round($amount, 2));

        if ($baseAmount < $threshold || $rate <= 0) {
            return [
                'base_amount' => $baseAmount,
                'withholding_amount' => 0.0,
                'net_settlement_amount' => $baseAmount,
                'rate' => round($rate, 4),
                'service_type' => $serviceType,
                'counterparty_type' => $counterpartyType,
                'certificate_reference' => null,
            ];
        }

        $withholdingAmount = round($baseAmount * ($rate / 100), 2);
        $prefix = (string) ($rule['certificate_prefix'] ?? $rule->certificate_prefix ?? 'WHT');
        $certificateReference = trim((string) ($options['certificate_reference'] ?? ''));
        if ($certificateReference === '') {
            $certificateReference = $prefix . '-' . now()->format('YmdHis');
        }

        return [
            'base_amount' => $baseAmount,
            'withholding_amount' => $withholdingAmount,
            'net_settlement_amount' => round($baseAmount - $withholdingAmount, 2),
            'rate' => round($rate, 4),
            'service_type' => $serviceType,
            'counterparty_type' => $counterpartyType,
            'certificate_reference' => $certificateReference,
        ];
    }

    public function calculateNigeriaPaye(float $grossMonthlyPay, array $options = []): array
    {
        $grossAnnual = max(0, $grossMonthlyPay) * 12;
        $pensionAnnual = max(0, (float) ($options['pension_employee_annual'] ?? 0));
        $nhfAnnual = max(0, (float) ($options['nhf_annual'] ?? 0));
        $otherReliefs = max(0, (float) ($options['other_reliefs_annual'] ?? 0));

        $craFixed = 200000;
        $craVariable = max($grossAnnual * 0.01, $craFixed) + ($grossAnnual * 0.20);
        $consolidatedRelief = max($craFixed, $craVariable);

        $taxableIncome = max(0, $grossAnnual - $consolidatedRelief - $pensionAnnual - $nhfAnnual - $otherReliefs);

        $bands = $options['bands'] ?? [
            ['cap' => 300000, 'rate' => 7],
            ['cap' => 300000, 'rate' => 11],
            ['cap' => 500000, 'rate' => 15],
            ['cap' => 500000, 'rate' => 19],
            ['cap' => 1600000, 'rate' => 21],
            ['cap' => null, 'rate' => 24],
        ];

        $remaining = $taxableIncome;
        $annualTax = 0.0;
        $bandBreakdown = [];

        foreach ($bands as $band) {
            if ($remaining <= 0) {
                break;
            }

            $cap = $band['cap'];
            $rate = (float) $band['rate'];
            $chargeable = $cap === null ? $remaining : min($remaining, (float) $cap);
            $tax = $chargeable * ($rate / 100);
            $annualTax += $tax;
            $remaining -= $chargeable;

            $bandBreakdown[] = [
                'chargeable' => round($chargeable, 2),
                'rate' => round($rate, 2),
                'tax' => round($tax, 2),
            ];
        }

        return [
            'gross_annual' => round($grossAnnual, 2),
            'consolidated_relief' => round($consolidatedRelief, 2),
            'pension_annual' => round($pensionAnnual, 2),
            'nhf_annual' => round($nhfAnnual, 2),
            'other_reliefs_annual' => round($otherReliefs, 2),
            'taxable_income_annual' => round($taxableIncome, 2),
            'annual_tax' => round($annualTax, 2),
            'monthly_tax' => round($annualTax / 12, 2),
            'bands' => $bandBreakdown,
        ];
    }

    public function summariseByType(iterable $lines): array
    {
        return collect($lines)
            ->groupBy('tax_type')
            ->map(function (Collection $group, string $type) {
                return [
                    'tax_type' => $type,
                    'base_amount' => round((float) $group->sum('base_amount'), 2),
                    'tax_amount' => round((float) $group->sum('tax_amount'), 2),
                    'recoverable_amount' => round((float) $group->sum('recoverable_amount'), 2),
                    'non_recoverable_amount' => round((float) $group->sum('non_recoverable_amount'), 2),
                ];
            })
            ->values()
            ->all();
    }
}

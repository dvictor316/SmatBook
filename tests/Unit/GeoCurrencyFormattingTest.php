<?php

namespace Tests\Unit;

use App\Support\GeoCurrency;
use App\Support\CompactNumber;
use PHPUnit\Framework\TestCase;

class GeoCurrencyFormattingTest extends TestCase
{
    public function test_repeated_formatting_keeps_amounts_currencies_and_locales_independent(): void
    {
        foreach (range(1, 3) as $pass) {
            foreach (['en-NG', 'de-DE', 'fr-FR'] as $locale) {
                foreach (['NGN', 'USD', 'EUR'] as $currency) {
                    foreach ([0, 1234567.89, -2500.5, 0.01] as $amount) {
                        $expected = number_format(abs($amount), 2);
                        if (class_exists(\NumberFormatter::class)) {
                            $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
                            $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);
                            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
                            $expected = CompactNumber::trimZeroFraction(
                                $formatter->format(abs($amount)),
                                $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL),
                                2
                            );
                        } else {
                            $expected = CompactNumber::format(abs($amount), 2);
                        }
                        $this->assertSame(
                            ($amount < 0 ? '-' : '').GeoCurrency::symbol($currency).$expected,
                            GeoCurrency::format($amount, $currency, $currency, $locale)
                        );
                    }
                }
            }
        }
    }

    public function test_compact_number_removes_only_a_zero_fraction(): void
    {
        $this->assertSame('7,000', CompactNumber::format(7000, 2));
        $this->assertSame('7,000.50', CompactNumber::format(7000.5, 2));
        $this->assertSame('0.01', CompactNumber::format(0.01, 2));
        $this->assertSame('-7,000', CompactNumber::format(-7000, 2));
        $this->assertSame('7.000', CompactNumber::format(7000, 2, ',', '.'));
        $this->assertSame('7,000.000', CompactNumber::format(7000, 3));
    }
}

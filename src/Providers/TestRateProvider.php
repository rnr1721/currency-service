<?php

declare(strict_types=1);

namespace rnr1721\CurrencyService\Providers;

use rnr1721\CurrencyService\Contracts\CurrencyRateProviderInterface;
use rnr1721\CurrencyService\Exceptions\CurrencyProviderException;

/**
 * Test provider for currency rates
 *
 * This provider returns predefined exchange rates for testing purposes.
 * Returns rates in format: how much base currency for 1 unit of other currency
 */
class TestRateProvider implements CurrencyRateProviderInterface
{
    /**
     * Predefined rates - how much USD for 1 unit of currency
     *
     * @var array<string, float>
     */
    private array $baseRates = [
        'EUR' => 1.087,    // 1.087 USD for 1 EUR
        'GBP' => 1.266,    // 1.266 USD for 1 GBP
        'JPY' => 0.00674,  // 0.00674 USD for 1 JPY
        'CHF' => 1.149,    // 1.149 USD for 1 CHF
        'AUD' => 0.658,    // 0.658 USD for 1 AUD
        'CAD' => 0.741,    // 0.741 USD for 1 CAD
        'NZD' => 0.61,     // 0.61 USD for 1 NZD
        'CNY' => 0.139,    // 0.139 USD for 1 CNY
    ];

    /**
     * Get exchange rates for the specified base currency
     *
     * Returns rates in format: how much base currency for 1 unit of other currency
     *
     * @param string $baseCurrency Base currency code
     * @return array<string, float> Exchange rates
     * @throws CurrencyProviderException If base currency is not supported
     */
    public function getRates(string $baseCurrency): array
    {
        // We only support USD as base currency
        if ($baseCurrency !== 'USD') {
            throw new CurrencyProviderException(
                "Currency {$baseCurrency} is not supported by TestRateProvider"
            );
        }

        return $this->baseRates;
    }

    /**
     * Add a test rate
     *
     * @param string $currency Currency code to add
     * @param float $rateToUSD How much USD for 1 unit of currency
     */
    public function addRate(string $currency, float $rateToUSD): void
    {
        $this->baseRates[$currency] = $rateToUSD;
    }

    /**
     * Reset rates to default values
     */
    public function reset(): void
    {
        $this->baseRates = [
            'EUR' => 1.087,
            'GBP' => 1.266,
            'JPY' => 0.00674,
            'CHF' => 1.149,
            'AUD' => 0.658,
            'CAD' => 0.741,
            'NZD' => 0.61,
            'CNY' => 0.139,
        ];
    }
}

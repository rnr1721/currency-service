<?php

declare(strict_types=1);

namespace rnr1721\CurrencyService\Providers;

use rnr1721\CurrencyService\Contracts\CurrencyRateProviderInterface;
use rnr1721\CurrencyService\Exceptions\CurrencyProviderException;

/**
 * Test provider for currency rates
 *
 * This provider returns predefined exchange rates for testing purposes.
 * It uses common currency pairs with realistic but static rates.
 */
class TestRateProvider implements CurrencyRateProviderInterface
{
    /**
     * Predefined base rates against USD
     *
     * @var array<string, float>
     */
    private array $baseRates = [
        'EUR' => 0.92,
        'GBP' => 0.79,
        'JPY' => 148.41,
        'CHF' => 0.87,
        'AUD' => 1.52,
        'CAD' => 1.35,
        'NZD' => 1.64,
        'CNY' => 7.19,
    ];

    /**
     * Get exchange rates for the specified base currency
     *
     * @param string $baseCurrency Base currency code
     * @return array<string, float> Exchange rates
     * @throws CurrencyProviderException If base currency is not supported
     */
    public function getRates(string $baseCurrency): array
    {
        // if USD is requested, return base rates
        if ($baseCurrency === 'USD') {
            return $this->baseRates;
        }

        // if requested currency is not supported, throw an exception
        if (!isset($this->baseRates[$baseCurrency])) {
            throw new CurrencyProviderException(
                "Currency {$baseCurrency} is not supported by TestRateProvider"
            );
        }

        $rates = [];
        $baseRate = $this->baseRates[$baseCurrency];

        // USD is always added
        $rates['USD'] = 1 / $baseRate;

        // Convert other currencies through USD
        foreach ($this->baseRates as $currency => $rate) {
            if ($currency !== $baseCurrency) {
                $rates[$currency] = $rate / $baseRate;
            }
        }

        return $rates;
    }

    /**
     * Add a test rate
     *
     * Allows adding custom rates for testing specific scenarios
     *
     * @param string $currency Currency code to add
     * @param float $rateToUSD Exchange rate to USD
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
            'EUR' => 0.92,
            'GBP' => 0.79,
            'JPY' => 148.41,
            'CHF' => 0.87,
            'AUD' => 1.52,
            'CAD' => 1.35,
            'NZD' => 1.64,
            'CNY' => 7.19,
        ];
    }
}

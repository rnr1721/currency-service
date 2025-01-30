<?php

namespace rnr1721\CurrencyService\Contracts;

use rnr1721\CurrencyService\Exceptions\CurrencyProviderException;

/**
 * Interface for currency rate providers
 *
 * This interface defines the contract for services that fetch currency exchange rates
 * from external sources (like API providers, banks, financial services).
 * Implementations should handle the specific logic for each data source.
 */
interface CurrencyRateProviderInterface
{
    /**
     * Get current exchange rates for a base currency
     *
     * Fetches current exchange rates from the provider using the specified base currency.
     * The returned rates should be relative to the base currency (e.g., if base is USD,
     * rates show how many units of other currencies equal 1 USD).
     *
     * @param string $baseCurrency The base currency code (e.g., 'USD', 'EUR')
     *
     * @return array<string, float> Array of exchange rates where:
     *                              - key is the currency code (e.g., 'EUR', 'GBP')
     *                              - value is the exchange rate relative to base currency
     *
     * @throws CurrencyProviderException When unable to fetch rates or on invalid response
     *
     * @example [
     *     'EUR' => 0.92,  // 1 USD = 0.92 EUR
     *     'GBP' => 0.79,  // 1 USD = 0.79 GBP
     *     'JPY' => 148.41 // 1 USD = 148.41 JPY
     * ]
     */
    public function getRates(string $baseCurrency): array;
}

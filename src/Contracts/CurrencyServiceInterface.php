<?php

namespace rnr1721\CurrencyService\Contracts;

use rnr1721\CurrencyService\DTO\ConversionSettingsDTO;
use rnr1721\CurrencyService\DTO\CurrencyDTO;
use rnr1721\CurrencyService\DTO\CurrencyWithLatestRateDTO;
use rnr1721\CurrencyService\DTO\CurrencyRateDTO;
use rnr1721\CurrencyService\DTO\FormatSettingsDTO;
use rnr1721\CurrencyService\Exceptions\CurrencyProviderException;
use rnr1721\CurrencyService\Exceptions\NoCurrencyException;
use rnr1721\CurrencyService\Exceptions\CurrencyNotFoundException;
use rnr1721\CurrencyService\Exceptions\CurrencyRateNotFoundException;
use DateTimeInterface;
use InvalidArgumentException;

/**
* Main interface for currency operations
*
* This interface provides the primary API for currency-related operations including
* conversion, formatting, rate management, and default currency handling.
* It serves as the main entry point for currency functionality.
*/
interface CurrencyServiceInterface
{
    /**
     * Get the system's default currency
     *
     * Retrieves the currently configured default currency.
     *
     * @return CurrencyDTO Details of the default currency
     * @throws NoCurrencyException If no default currency is set
     */
    public function getDefaultCurrency(): CurrencyDTO;

    /**
     * Set the system's default currency
     *
     * Changes the default currency used by the system.
     *
     * @param string $currencyCode The currency code to set as default (e.g., 'USD', 'EUR')
     * @throws CurrencyNotFoundException If the specified currency does not exist
     * @return void
     */
    public function setDefaultCurrency(string $currencyCode): void;

    /**
     * Convert an amount between currencies
     *
     * Converts a monetary amount from one currency to another using current exchange rates.
     * Optional conversion settings can be provided to control rounding behavior.
     *
     * @param float $amount The amount to convert
     * @param string $from Source currency code (e.g., 'USD')
     * @param string|null $to Target currency code, default currency if null (e.g., 'EUR')
     * @param ConversionSettingsDTO|null $settings Optional conversion settings for rounding control
     *
     * @return float The converted amount
     * @throws CurrencyNotFoundException If either currency does not exist
     * @throws CurrencyRateNotFoundException If no exchange rate is available
     * @throws InvalidArgumentException When amount is negative
     */
    public function convert(
        float $amount,
        string $from,
        ?string $to = null,
        ?ConversionSettingsDTO $settings = null
    ): float;

    /**
     * Format a monetary amount
     *
     * Formats a monetary amount according to the specified settings and locale.
     * This includes proper decimal places, separators, and currency positioning.
     *
     * @param float $amount The amount to format
     * @param string|null $currencyCode Currency code for the amount, null is default currency (e.g., 'USD')
     * @param FormatSettingsDTO|null $settings Optional formatting settings
     *
     * @return string The formatted amount with currency
     * @throws CurrencyNotFoundException If the specified currency doesn't exist
     *
     * @example "$1,234.56" or "1.234,56 €" depending on settings
     */
    public function format(
        float $amount,
        ?string $currencyCode = null,
        ?FormatSettingsDTO $settings = null
    ): string;

    /**
     * Get all available currencies
     *
     * Retrieves a list of all currencies configured in the system.
     *
     * @return array<int, CurrencyDTO> Array of all available currencies
     */
    public function getAllCurrencies(): array;

    /**
     * Retrieve all currencies with their latest exchange rates, using cache for optimization.
     *
     * This method will first check if the result is cached and return it if available.
     * If not, it will call `getAllWithLatestRates` to fetch the latest data, cache it,
     * and then return the result.
     *
     * @return CurrencyWithLatestRateDTO[] List of all currencies with their latest exchange rates.
     */
    public function getAllCurrenciesWithRates(): array;

    /**
     * Get current exchange rate between two currencies
     *
     * Retrieves the current exchange rate for a currency pair.
     *
     * @param string $from Source currency code (e.g., 'USD')
     * @param string|null $to Target currency code, null is default (e.g., 'EUR')
     *
     * @return CurrencyRateDTO The current exchange rate details
     * @throws CurrencyNotFoundException If either currency doesn't exist
     * @throws CurrencyRateNotFoundException If no rate is available
     */
    public function getCurrencyRate(
        string $from,
        ?string $to = null
    ): CurrencyRateDTO;

    /**
     * Save rate relative to default currency
     *
     * @param string $currencyCode Currency code
     * @param float $rate Rate value
     * @param DateTimeInterface|null $updatedAt Optional update timestamp
     * @return void
     */
    public function saveDefaultRate(
        string $currencyCode,
        float $rate,
        ?\DateTimeInterface $updatedAt = null
    ): void;

    /**
     * Update all currency exchange rates
     *
     * Fetches the latest exchange rates from the configured provider
     * and updates them in the system.
     *
     * @throws CurrencyProviderException If there's an error fetching rates
     * @return void
     */
    public function updateRates(): void;
}

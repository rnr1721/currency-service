<?php

namespace rnr1721\CurrencyService\Contracts;

use rnr1721\CurrencyService\DTO\CurrencyDTO;
use rnr1721\CurrencyService\DTO\CurrencyWithLatestRateDTO;
use rnr1721\CurrencyService\DTO\CurrencyRateDTO;
use rnr1721\CurrencyService\Exceptions\CurrencyNotFoundException;
use rnr1721\CurrencyService\Exceptions\CurrencyRateNotFoundException;
use rnr1721\CurrencyService\Exceptions\NoCurrencyException;

/**
 * Interface for currency data persistence
 *
 * This interface defines the contract for storing and retrieving currency data,
 * including currency details, exchange rates, and default currency settings.
 * Implementations should handle the persistence logic for currencies and their rates.
 */
interface CurrencyRepositoryInterface
{
    /**
     * Set a currency as the system default
     *
     * Updates the system to use the specified currency as the default currency.
     * Only one currency can be default at a time, so this method should ensure
     * any previously default currency is unset.
     *
     * @param string $code Currency code to set as default (e.g., 'USD', 'EUR')
     * @throws CurrencyNotFoundException If the specified currency code does not exist
     * @return void
     */
    public function setDefaultCurrency(string $code): void;

    /**
     * Find a currency by its code
     *
     * Retrieves currency details for the specified currency code.
     *
     * @param string $code The currency code to find (e.g., 'USD', 'EUR')
     * @return CurrencyDTO|null Currency details if found, null otherwise
     */
    public function findByCode(string $code): ?CurrencyDTO;

    /**
     * Save or update currency details
     *
     * Persists currency information to the storage. If the currency already exists,
     * it should be updated; if it doesn't exist, it should be created.
     *
     * @param CurrencyDTO $currency The currency data to save
     * @return void
     */
    public function save(CurrencyDTO $currency): void;

    /**
     * Save a currency exchange rate
     *
     * Records a new exchange rate between two currencies at a specific point in time.
     * This creates a historical record of the rate.
     *
     * @param CurrencyRateDTO $rate The exchange rate data to save
     * @throws CurrencyNotFoundException If either the source or target currency does not exist
     * @return void
     */
    public function saveRate(CurrencyRateDTO $rate): void;

    /**
     * Get historical exchange rates for a currency pair
     *
     * Retrieves all exchange rates between two currencies within the specified date range.
     *
     * @param string $fromCurrency Source currency code
     * @param string $toCurrency Target currency code
     * @param \DateTime $from Start date for the history period
     * @param \DateTime $to End date for the history period
     *
     * @return array<int, CurrencyRateDTO> Array of historical rates
     * @throws CurrencyNotFoundException If either currency does not exist
     */
    public function getRateHistory(string $fromCurrency, string $toCurrency, \DateTime $from, \DateTime $to): array;

    /**
     * Find the current default currency
     *
     * Retrieves the currency that is currently set as default in the system.
     *
     * @return CurrencyDTO|null The default currency if set, null otherwise
     */
    public function findDefaultCurrency(): ?CurrencyDTO;

    /**
     * Retrieve all currencies with their latest exchange rates.
     *
     * This method returns a list of all currencies along with their most recent
     * exchange rates relative to the default currency. It ensures that each
     * currency is returned with its code, name, whether it's the default currency,
     * and the latest rate.
     *
     * @return CurrencyWithLatestRateDTO[] An array of DTOs representing all currencies with their latest rates.
     * @throws NoCurrencyException If no default currency is set.
     */
    public function getAllWithLatestRates(): array;

    /**
     * Get all available currencies
     *
     * Retrieves all currencies configured in the system.
     *
     * @return array<int, CurrencyDTO> Array of all currencies
     */
    public function getAll(): array;

    /**
     * Retrieve the latest exchange rate for a currency pair.
     *
     * The method tries following strategies to find the rate:
     * 1. Direct rate between currencies
     * 2. Inverse rate (if direct not found)
     * 3. Cross rate through default currency (if neither direct nor inverse found)
     *
     * @param string $from The source currency code.
     * @param string $to The target currency code.
     * @return CurrencyRateDTO A DTO containing the exchange rate data.
     * @throws CurrencyRateNotFoundException If no rate is found using any strategy
     * @throws NoCurrencyException If default currency is not set when trying cross-rate
     */
    public function getLatestRate(string $from, string $to): CurrencyRateDTO;

    /**
     * Delete a currency and its associated rate history.
     *
     * This method deletes a currency from the system, and also removes all exchange rate
     * records that involve this currency.
     *
     * @param string $code The currency code to delete.
     * @return void
     * @throws CurrencyRateNotFoundException If no currency with the given code is found.
     */
    public function deleteCurrency(string $code): void;
}

<?php

namespace rnr1721\CurrencyService\Contracts;

use rnr1721\CurrencyService\DTO\CurrencyRateHistoryDTO;
use rnr1721\CurrencyService\DTO\CurrencyRateDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use DateTimeInterface;

/**
 * Interface for managing currency rate history
 *
 * This interface provides methods for retrieving and analyzing historical currency exchange rates.
 * It supports pagination, filtering, and statistical analysis of rate changes over time.
 */
interface CurrencyRateHistoryRepositoryInterface
{
    /**
     * Get paginated currency rate history
     *
     * Retrieves historical currency rates with pagination support and optional filtering.
     *
     * @param string|null $fromCurrency The source currency code (e.g., 'USD'), or null for all source currencies
     * @param string|null $toCurrency The target currency code (e.g., 'EUR'), or null for all target currencies
     * @param DateTimeInterface|null $startDate Start date for filtering, or null for no start date limit
     * @param DateTimeInterface|null $endDate End date for filtering, or null for no end date limit
     * @param string $orderBy Column to sort by (default: 'created_at')
     * @param string $orderDirection Sort direction: 'asc' or 'desc' (default: 'desc')
     * @param int $perPage Number of items per page (default: 15)
     *
     * @return LengthAwarePaginator Paginated collection of currency rates
     */
    public function getPaginatedHistory(
        ?string $fromCurrency = null,
        ?string $toCurrency = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $orderBy = 'created_at',
        string $orderDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * Get complete currency rate history without pagination
     *
     * Retrieves all historical currency rates matching the specified criteria.
     *
     * @param string|null $fromCurrency The source currency code (e.g., 'USD'), or null for all source currencies
     * @param string|null $toCurrency The target currency code (e.g., 'EUR'), or null for all target currencies
     * @param DateTimeInterface|null $startDate Start date for filtering, or null for no start date limit
     * @param DateTimeInterface|null $endDate End date for filtering, or null for no end date limit
     * @param string $orderBy Column to sort by (default: 'created_at')
     * @param string $orderDirection Sort direction: 'asc' or 'desc' (default: 'desc')
     *
     * @return CurrencyRateDTO[] Array of currency rate DTOs
     */
    public function getAllHistory(
        ?string $fromCurrency = null,
        ?string $toCurrency = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $orderBy = 'created_at',
        string $orderDirection = 'desc'
    ): array;

    /**
     * Get statistical data for currency rates over a period
     *
     * Calculates statistical metrics (min, max, average) for the specified currency pair
     * within the given date range.
     *
     * @param string $fromCurrency The source currency code (e.g., 'USD')
     * @param string $toCurrency The target currency code (e.g., 'EUR')
     * @param DateTimeInterface $startDate Start date for the analysis period
     * @param DateTimeInterface $endDate End date for the analysis period
     *
     * @return CurrencyRateHistoryDTO DTO containing statistical data
     */
    public function getHistoryStats(
        string $fromCurrency,
        string $toCurrency,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate
    ): CurrencyRateHistoryDTO;

    /**
     * Get currency rates for specific dates
     *
     * Retrieves exchange rates for a currency pair on the specified dates.
     * For each date, returns the latest rate available on that day.
     *
     * @param string $fromCurrency The source currency code (e.g., 'USD')
     * @param string $toCurrency The target currency code (e.g., 'EUR')
     * @param DateTimeInterface[] $dates Array of dates to get rates for
     *
     * @return array<string, CurrencyRateDTO> Array of rates indexed by date string (Y-m-d format)
     */
    public function getRatesForDates(
        string $fromCurrency,
        string $toCurrency,
        array $dates
    ): array;
}

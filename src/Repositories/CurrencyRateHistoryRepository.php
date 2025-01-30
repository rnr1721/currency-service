<?php

namespace rnr1721\CurrencyService\Repositories;

use rnr1721\CurrencyService\Contracts\CurrencyRateHistoryRepositoryInterface;
use rnr1721\CurrencyService\DTO\CurrencyRateHistoryDTO;
use rnr1721\CurrencyService\DTO\CurrencyRateDTO;
use rnr1721\CurrencyService\Models\CurrencyRate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use rnr1721\CurrencyService\DTO\RateStatisticsDTO;
use RuntimeException;

/**
 * Repository for currency rate history
 * @package category\Repositories
 */
class CurrencyRateHistoryRepository implements CurrencyRateHistoryRepositoryInterface
{
    /**
     * Retrieve paginated currency rate history.
     *
     * @param string|null $fromCurrency The source currency.
     * @param string|null $toCurrency The target currency.
     * @param DateTimeInterface|null $startDate The start date of the period.
     * @param DateTimeInterface|null $endDate The end date of the period.
     * @param string $orderBy The field to order by. Default is 'created_at'.
     * @param string $orderDirection The order direction ('asc' or 'desc'). Default is 'desc'.
     * @param int $perPage Number of records per page. Default is 15.
     * @return LengthAwarePaginator A paginated collection of data.
     */
    public function getPaginatedHistory(
        ?string $fromCurrency = null,
        ?string $toCurrency = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $orderBy = 'created_at',
        string $orderDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->getBaseHistoryQuery(
            $fromCurrency,
            $toCurrency,
            $startDate,
            $endDate
        )
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage)
            ->through(fn ($rate) => $this->mapToDTO($rate));
    }

    /**
     * Retrieve all currency rate history without pagination.
     *
     * @param string|null $fromCurrency The source currency.
     * @param string|null $toCurrency The target currency.
     * @param DateTimeInterface|null $startDate The start date of the period.
     * @param DateTimeInterface|null $endDate The end date of the period.
     * @param string $orderBy The field to order by. Default is 'created_at'.
     * @param string $orderDirection The order direction ('asc' or 'desc'). Default is 'desc'.
     * @return CurrencyRateDTO[] An array of DTOs with currency rate data.
     */
    public function getAllHistory(
        ?string $fromCurrency = null,
        ?string $toCurrency = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null,
        string $orderBy = 'created_at',
        string $orderDirection = 'desc'
    ): array {
        return $this->getBaseHistoryQuery(
            $fromCurrency,
            $toCurrency,
            $startDate,
            $endDate
        )
            ->orderBy($orderBy, $orderDirection)
            ->get()
            ->map(fn ($rate) => $this->mapToDTO($rate))
            ->toArray();
    }

    /**
     * Retrieve statistics for currency rates over a specified period.
     *
     * @param string $fromCurrency The source currency.
     * @param string $toCurrency The target currency.
     * @param DateTimeInterface $startDate The start date of the period.
     * @param DateTimeInterface $endDate The end date of the period.
     * @return CurrencyRateHistoryDTO DTO containing currency rate statistics.
     */
    public function getHistoryStats(
        string $fromCurrency,
        string $toCurrency,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate
    ): CurrencyRateHistoryDTO {
        $query = $this->getBaseHistoryQuery(
            $fromCurrency,
            $toCurrency,
            $startDate,
            $endDate
        )
            ->selectRaw('
                MIN(rate) as min_rate,
                MAX(rate) as max_rate,
                AVG(rate) as avg_rate,
                COUNT(*) as total_records
            ');

        $result = $query->first();

        if (!$result) {
            throw new RuntimeException('No rates found for the specified period');
        }

        $stats = RateStatisticsDTO::fromDatabaseResult($result);

        return new CurrencyRateHistoryDTO(
            fromCurrency: $fromCurrency,
            toCurrency: $toCurrency,
            startDate: $startDate,
            endDate: $endDate,
            minRate: (float)$stats->min_rate,
            maxRate: (float)$stats->max_rate,
            averageRate: (float)$stats->avg_rate,
            totalRecords: (int)$stats->total_records
        );
    }

    /**
     * Retrieve currency rates for specific dates.
     *
     * @param string $fromCurrency The source currency.
     * @param string $toCurrency The target currency.
     * @param DateTimeInterface[] $dates An array of dates.
     * @return array<string, CurrencyRateDTO> An associative array where the key is the date,
     *                                        and the value is a DTO with the rate.
     */
    public function getRatesForDates(
        string $fromCurrency,
        string $toCurrency,
        array $dates
    ): array {
        $result = [];

        foreach ($dates as $date) {
            $rate = $this->getBaseHistoryQuery($fromCurrency, $toCurrency)
                ->whereDate('created_at', $date)
                ->latest('created_at')
                ->first();

            if ($rate) {
                $result[$date->format('Y-m-d')] = $this->mapToDTO($rate);
            }
        }

        return $result;
    }

    /**
     * Create the base query for retrieving currency rate history.
     *
     * @param string|null $fromCurrency The source currency.
     * @param string|null $toCurrency The target currency.
     * @param DateTimeInterface|null $startDate The start date of the period.
     * @param DateTimeInterface|null $endDate The end date of the period.
     * @return Builder The query builder for currency rate history.
     */
    private function getBaseHistoryQuery(
        ?string $fromCurrency = null,
        ?string $toCurrency = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): Builder {
        $query = CurrencyRate::query();

        if ($fromCurrency) {
            $query->where('from_currency', $fromCurrency);
        }

        if ($toCurrency) {
            $query->where('to_currency', $toCurrency);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Map a currency rate model to a DTO.
     *
     * @param Model $rate The currency rate model.
     * @throws RuntimeException If rate model is invalid
     * @return CurrencyRateDTO A DTO containing currency rate data.
     */
    private function mapToDTO(Model $rate): CurrencyRateDTO
    {

        if (!$rate instanceof CurrencyRate) {
            throw new RuntimeException('Invalid rate model type, need CurrencyRate');
        }

        if (!$rate->created_at) {
            throw new RuntimeException('Rate created_at is null');
        }

        return new CurrencyRateDTO(
            fromCurrency: $rate->from_currency,
            toCurrency: $rate->to_currency,
            rate: $rate->rate,
            updatedAt: $rate->created_at
        );
    }
}

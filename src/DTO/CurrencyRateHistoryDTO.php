<?php

namespace rnr1721\CurrencyService\DTO;

use DateTimeInterface;

/**
 * Class CurrencyRateHistoryDTO
 * Data Transfer Object for currency rate history
 * @package rnr1721\CurrencyService\DTO
 */
class CurrencyRateHistoryDTO
{
    /**
     * CurrencyRateHistoryDTO constructor.
     * @param string $fromCurrency The currency code of the currency to convert from
     * @param string $toCurrency The currency code of the currency to convert to
     * @param DateTimeInterface $startDate The start date of the rate history
     * @param DateTimeInterface $endDate The end date of the rate history
     * @param float $minRate The minimum rate in the history
     * @param float $maxRate The maximum rate in the history
     * @param float $averageRate The average rate in the history
     * @param int $totalRecords The total number of records in the history
     */
    public function __construct(
        public string $fromCurrency,
        public string $toCurrency,
        public DateTimeInterface $startDate,
        public DateTimeInterface $endDate,
        public float $minRate,
        public float $maxRate,
        public float $averageRate,
        public int $totalRecords
    ) {
    }

    /**
     * Get the percentage change between the maximum and minimum rate
     * @return float
     */
    public function getChangePercentage(): float
    {
        if ($this->minRate == 0) {
            return 0;
        }

        return (($this->maxRate - $this->minRate) / $this->minRate) * 100;
    }

    /**
     * Check if the volatility was above the given percentage
     * @param float $threshold
     * @return bool
     */
    public function isVolatile(float $threshold = 5.0): bool
    {
        return $this->getChangePercentage() > $threshold;
    }
}

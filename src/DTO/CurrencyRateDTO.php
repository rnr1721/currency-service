<?php

namespace rnr1721\CurrencyService\DTO;

/**
 * Class CurrencyRateDTO
 * Class for storing currency rate data
 * @package rnr1721\CurrencyService\DTO
 */
class CurrencyRateDTO
{
    /**
     * CurrencyRateDTO constructor.
     * @param string $fromCurrency From currency code
     * @param string $toCurrency To currency code
     * @param float $rate Currency rate
     * @param \DateTimeInterface $updatedAt Date of last update
     */
    public function __construct(
        public string $fromCurrency,
        public string $toCurrency,
        public float $rate,
        public \DateTimeInterface $updatedAt
    ) {
    }
}

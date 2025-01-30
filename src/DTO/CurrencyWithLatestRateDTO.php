<?php

namespace rnr1721\CurrencyService\DTO;

/**
 * Class CurrencyWithLatestRateDTO
 *
 * This class represents a data transfer object (DTO) for a currency,
 * along with its latest exchange rate.
 *
 * It encapsulates the currency's code, name, whether it's the default currency,
 * and the latest exchange rate (relative to a default currency).
 *
 * This DTO can be used when retrieving a list of currencies along with their
 * latest exchange rates for various use cases such as displaying the list of
 * available currencies with their exchange rates.
 *
 * @package rnr1721\CurrencyService\DTO
 */
class CurrencyWithLatestRateDTO
{
    /**
     * CurrencyWithLatestRateDTO constructor.
     *
     * @param string $code Currency code (e.g., 'USD', 'EUR').
     * @param string $name The full name of the currency (e.g., 'US Dollar', 'Euro').
     * @param bool $isDefault Indicates whether this currency is the default currency.
     * @param float $latestRate The most recent exchange rate for this currency relative to the default currency.
     */
    public function __construct(
        public string $code,
        public string $name,
        public bool $isDefault = false,
        public float $latestRate = 0.00
    ) {
    }
}

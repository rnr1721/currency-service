<?php

namespace rnr1721\CurrencyService\DTO;

/**
 * Class FormatSettingsDTO
 * This class is used to store the settings for formatting the currency.
 * @package rnr1721\CurrencyService\DTO
 */
class FormatSettingsDTO
{
    /**
     * FormatSettingsDTO constructor.
     * @param int|null $decimals The number of decimals to show.
     * @param string|null $decimalSeparator The decimal separator.
     * @param string|null $thousandsSeparator The thousands separator.
     * @param bool $showCurrencyCode Whether to show the currency code or not.
     * @param string $currencyPosition The position of the currency symbol. Can be 'before' or 'after'.
     */
    public function __construct(
        public ?int $decimals = null,
        public ?string $decimalSeparator = null,
        public ?string $thousandsSeparator = null,
        public bool $showCurrencyCode = false,
        public string $currencyPosition = 'after' // 'before' or 'after'
    ) {
    }
}

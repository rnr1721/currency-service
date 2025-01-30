<?php

namespace rnr1721\CurrencyService\Contracts;

use rnr1721\CurrencyService\DTO\FormatSettingsDTO;

/**
 * Interface for formatting currency amounts.
 */
interface CurrencyFormatterServiceInterface
{
    /**
     * Format a currency amount.
     *
     * @param float $amount The numeric amount to format.
     * @param string $currencyCode The currency code (e.g., USD, EUR).
     * @param FormatSettingsDTO|null $settings Optional format settings to override defaults.
     * @return string The formatted currency string.
     */
    public function format(
        float $amount,
        string $currencyCode,
        ?FormatSettingsDTO $settings = null,
    ): string;
}

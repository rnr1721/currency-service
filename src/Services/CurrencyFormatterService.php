<?php

declare(strict_types=1);

namespace rnr1721\CurrencyService\Services;

use rnr1721\CurrencyService\Contracts\CurrencyFormatterServiceInterface;
use rnr1721\CurrencyService\DTO\FormatSettingsDTO;

/**
 * Service for formatting currency amounts.
 */
class CurrencyFormatterService implements CurrencyFormatterServiceInterface
{
    /**
     * CurrencyFormatterService constructor.
     *
     * @param array $config Configuration settings for currency formatting.
     */
    public function __construct(
        private readonly array $config
    ) {
    }

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
    ): string {
        $settings = $this->resolveSettings($settings);

        $formattedNumber = number_format(
            $amount,
            (int) $settings->decimals,
            $settings->decimalSeparator,
            $settings->thousandsSeparator
        );

        if (!$settings->showCurrencyCode) {
            return $formattedNumber;
        }

        return $settings->currencyPosition === 'before'
            ? "{$currencyCode} {$formattedNumber}"
            : "{$formattedNumber} {$currencyCode}";
    }

    /**
     * Resolve format settings by merging defaults, configuration, and provided settings.
     *
     * @param FormatSettingsDTO|null $settings Optional custom format settings.
     * @return FormatSettingsDTO The resolved format settings.
     */
    private function resolveSettings(?FormatSettingsDTO $settings): FormatSettingsDTO
    {
        $config = $this->config['formatting'] ?? [];

        if (!$settings) {
            return new FormatSettingsDTO(
                decimals: $config['decimals'] ?? 2,
                decimalSeparator: $config['decimal_separator'] ?? '.',
                thousandsSeparator: $config['thousands_separator'] ?? ',',
                showCurrencyCode: $config['show_currency_code'] ?? true,
                currencyPosition: $config['currency_position'] ?? 'after'
            );
        }

        return new FormatSettingsDTO(
            decimals: $settings->decimals ?? $config['decimals'] ?? 2,
            decimalSeparator: $settings->decimalSeparator ?? $config['decimal_separator'] ?? '.',
            thousandsSeparator: $settings->thousandsSeparator ?? $config['thousands_separator'] ?? ',',
            showCurrencyCode: $settings->showCurrencyCode ?? $config['show_currency_code'] ?? true,
            currencyPosition: $settings->currencyPosition ?? $config['currency_position'] ?? 'after'
        );
    }
}

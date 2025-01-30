<?php

declare(strict_types=1);

namespace rnr1721\CurrencyService\Services;

use rnr1721\CurrencyService\Contracts\CurrencyFormatterServiceInterface;
use rnr1721\CurrencyService\Contracts\CurrencyServiceInterface;
use rnr1721\CurrencyService\Contracts\CurrencyRepositoryInterface;
use rnr1721\CurrencyService\Contracts\CurrencyRateProviderInterface;
use rnr1721\CurrencyService\DTO\ConversionSettingsDTO;
use rnr1721\CurrencyService\DTO\CurrencyDTO;
use rnr1721\CurrencyService\DTO\CurrencyWithLatestRateDTO;
use rnr1721\CurrencyService\DTO\CurrencyRateDTO;
use rnr1721\CurrencyService\DTO\FormatSettingsDTO;
use rnr1721\CurrencyService\Exceptions\CurrencyNotFoundException;
use rnr1721\CurrencyService\Exceptions\NoCurrencyException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Main service for managing currencies and their rates.
 */
class CurrencyService implements CurrencyServiceInterface
{
    /**
     * Cache tag for currency service data.
     */
    private const CACHE_TAG = 'currency_service';

    /**
     * @param CurrencyRepositoryInterface $repository Repository for accessing currency data.
     * @param CurrencyRateProviderInterface $provider Provider for retrieving currency rates.
     * @param CacheRepository $cache Cache storage for optimized data retrieval.
     * @param CurrencyFormatterServiceInterface $formatter Service for formatting currency amounts.
     * @param array $config Configuration settings.
     * @param int $cacheTTL Cache TTL parameter
     */
    public function __construct(
        private readonly CurrencyRepositoryInterface $repository,
        private readonly CurrencyRateProviderInterface $provider,
        private readonly CacheRepository $cache,
        private readonly CurrencyFormatterServiceInterface $formatter,
        private readonly array $config,
        private readonly int $cacheTTL
    ) {
    }

    /**
     * Convert an amount from one currency to another.
     *
     * @param float $amount The amount to convert.
     * @param string $from Source currency code.
     * @param string $to|null Target currency code (uses default currency if null).
     * @param ConversionSettingsDTO|null $settings Optional conversion settings (e.g., rounding).
     * @return float The converted amount.
     */
    public function convert(
        float $amount,
        string $from,
        ?string $to = null,
        ?ConversionSettingsDTO $settings = null
    ): float {
        $targetCurrency = $to ?? $this->getDefaultCurrency()->code;
        $settings = $this->validateAndGetSettings($settings);

        /** @var 1|2|3|4 $mode */
        $mode = $settings->roundingMode;

        if ($from === $targetCurrency) {
            return round($amount, (int) $settings->roundingPrecision, $mode);
        }

        $rate = $this->getCurrencyRate($from, $targetCurrency);

        $result = $amount * $rate->rate;

        return round($result, (int) $settings->roundingPrecision, $mode);
    }

    /**
     * Format a currency amount.
     *
     * @param float $amount The numeric amount to format.
     * @param string|null $currencyCode The currency code, null is default.
     * @param FormatSettingsDTO|null $settings Optional format settings.
     * @return string The formatted amount.
     */
    public function format(
        float $amount,
        ?string $currencyCode = null,
        ?FormatSettingsDTO $settings = null
    ): string {
        $currency = $currencyCode ?? $this->getDefaultCurrency()->code;
        return $this->formatter->format($amount, $currency, $settings);
    }

    /**
     * Set the default currency for the system.
     *
     * @param string $currencyCode The currency code to set as default.
     * @throws CurrencyNotFoundException If the currency does not exist.
     */
    public function setDefaultCurrency(string $currencyCode): void
    {
        // Check if currency exists
        $currency = $this->repository->findByCode($currencyCode);
        if (!$currency) {
            throw new CurrencyNotFoundException("Currency with code {$currencyCode} not found");
        }

        // Set new default currency
        $this->repository->setDefaultCurrency($currencyCode);

        // Clear cache
        /** @phpstan-ignore-next-line */
        $this->getCacheStore()->flush();
        ;
    }

    /**
     * Retrieve the exchange rate between two currencies.
     *
     * @param string $from Source currency code.
     * @param string|null $to Target currency code.
     * @return CurrencyRateDTO The exchange rate data.
     */
    public function getCurrencyRate(string $from, ?string $to = null): CurrencyRateDTO
    {
        $targetCurr = $to ?? $this->getDefaultCurrency()->code;

        if ($from === $targetCurr) {
            // If currencies is equal, return 1:1 rate
            return new CurrencyRateDTO(
                fromCurrency: $from,
                toCurrency: $targetCurr,
                rate: 1.0,
                updatedAt: new \DateTime()
            );
        }

        $cacheKey = "rate_{$from}_{$targetCurr}";

        return $this->getCacheStore()->remember(
            $cacheKey,
            $this->cacheTTL,
            function () use ($from, $targetCurr) {
                return $this->repository->getLatestRate($from, $targetCurr);
            }
        );
    }

    /**
     * Retrieve all available currencies.
     *
     * @return CurrencyDTO[] List of all currencies.
     */
    public function getAllCurrencies(): array
    {
        return $this->getCacheStore()->remember(
            'currencies',
            $this->cacheTTL,
            function () {
                return $this->repository->getAll();
            }
        );
    }

    /**
     * Retrieve all currencies with their latest exchange rates, using cache for optimization.
     *
     * This method will first check if the result is cached and return it if available.
     * If not, it will call `getAllWithLatestRates` to fetch the latest data, cache it,
     * and then return the result.
     *
     * @return CurrencyWithLatestRateDTO[] List of all currencies with their latest exchange rates.
     */
    public function getAllCurrenciesWithRates(): array
    {
        $cacheKey = 'currencies_with_rates';

        return $this->getCacheStore()->remember(
            $cacheKey,
            $this->cacheTTL,
            function () {
                return $this->repository->getAllWithLatestRates();
            }
        );
    }

    /**
     * Retrieve the default currency for the system.
     *
     * @return CurrencyDTO The default currency.
     * @throws NoCurrencyException If no default currency is set.
     */
    public function getDefaultCurrency(): CurrencyDTO
    {
        return $this->getCacheStore()->remember(
            'default_currency',
            $this->cacheTTL,
            function () {
                $currency = $this->repository->findDefaultCurrency();
                if (!$currency) {
                    throw new NoCurrencyException('No default currency set in the system');
                }
                return $currency;
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function saveDefaultRate(
        string $currencyCode,
        float $rate,
        ?\DateTimeInterface $updatedAt = null
    ): void {
        $defaultCurrency = $this->getDefaultCurrency();

        $this->repository->saveRate(new CurrencyRateDTO(
            fromCurrency: $currencyCode,
            toCurrency: $defaultCurrency->code,
            rate: $rate,
            updatedAt: $updatedAt ?? new \DateTime()
        ));

        /** @phpstan-ignore-next-line */
        $this->getCacheStore()->flush();
    }

    /**
     * Update currency rates using the provider and store them in the repository.
     * @return void
     */
    public function updateRates(): void
    {
        $defaultCurrency = $this->getDefaultCurrency();
        $rates = $this->provider->getRates($defaultCurrency->code);
        $timestamp = new \DateTime();

        foreach ($rates as $currencyCode => $rate) {
            $this->repository->saveRate(new CurrencyRateDTO(
                fromCurrency: $defaultCurrency->code,
                toCurrency: $currencyCode,
                rate: $rate,
                updatedAt: $timestamp
            ));
        }
        /** @phpstan-ignore-next-line */
        $this->getCacheStore()->flush();
    }

    /**
     * Validate and get conversion settings
     *
     * If settings are not provided, creates new settings from configuration.
     * Validates that rounding mode is one of the allowed values:
     * - HALF_UP (1)
     * - HALF_DOWN (2)
     * - HALF_EVEN (3)
     * - HALF_ODD (4)
     *
     * @param ConversionSettingsDTO|null $settings Settings to validate or null to create from config
     * @return ConversionSettingsDTO Validated settings
     * @throws \InvalidArgumentException If rounding mode is not valid
     */
    private function validateAndGetSettings(?ConversionSettingsDTO $settings): ConversionSettingsDTO
    {
        if ($settings === null) {
            $roundingMode = (int) $this->config['conversion']['rounding_mode'];
            if (!in_array($roundingMode, [1, 2, 3, 4], true)) {
                $errorMessage = 'Config error: rounding mode must be one of: HALF_UP, HALF_DOWN, HALF_EVEN, HALF_ODD';
                throw new \InvalidArgumentException($errorMessage);
            }

            /** @var 1|2|3|4 $roundingMode */
            $settings = new ConversionSettingsDTO(
                roundingPrecision: $this->config['conversion']['rounding_precision'],
                roundingMode: $roundingMode
            );
        }

        /** @var int $mode */
        $mode = $settings->roundingMode;
        if (!in_array($mode, [1, 2, 3, 4], true)) {
            $errorMessage = 'Rounding mode must be one of: HALF_UP, HALF_DOWN, HALF_EVEN, HALF_ODD';
            throw new \InvalidArgumentException($errorMessage);
        }

        return $settings;
    }

    private function getCacheStore(): CacheRepository
    {
        try {
            return $this->cache->tags([self::CACHE_TAG]);
        } catch (\BadMethodCallException $e) {
            return $this->cache;
        }
    }
}

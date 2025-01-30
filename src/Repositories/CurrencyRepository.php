<?php

namespace rnr1721\CurrencyService\Repositories;

use rnr1721\CurrencyService\Contracts\CurrencyRepositoryInterface;
use rnr1721\CurrencyService\DTO\CurrencyDTO;
use rnr1721\CurrencyService\DTO\CurrencyWithLatestRateDTO;
use rnr1721\CurrencyService\DTO\CurrencyRateDTO;
use rnr1721\CurrencyService\Models\Currency;
use rnr1721\CurrencyService\Models\CurrencyRate;
use rnr1721\CurrencyService\Exceptions\CurrencyRateNotFoundException;
use rnr1721\CurrencyService\Exceptions\NoCurrencyException;
use DateTime;

/**
 * Repository for managing currencies and their rates.
 */
class CurrencyRepository implements CurrencyRepositoryInterface
{
    /**
     * Find a currency by its code.
     *
     * @param string $code The currency code.
     * @return CurrencyDTO|null A DTO containing the currency data, or null if not found.
     */
    public function findByCode(string $code): ?CurrencyDTO
    {
        $currency = Currency::where('code', $code)->first();

        if (!$currency) {
            return null;
        }

        return new CurrencyDTO(
            code: $currency->code,
            name: $currency->name,
            isDefault: $currency->is_default
        );
    }

    /**
     * Retrieve the default currency.
     *
     * @return CurrencyDTO|null A DTO with the default currency data, or null if not set.
     */
    public function findDefaultCurrency(): ?CurrencyDTO
    {
        $currency = Currency::where('is_default', true)->first();

        if (!$currency) {
            return null;
        }

        return new CurrencyDTO(
            code: $currency->code,
            name: $currency->name,
            isDefault: true
        );
    }

    /**
     * Retrieve all currencies.
     *
     * @return CurrencyDTO[] An array of DTOs representing all currencies.
     */
    public function getAll(): array
    {
        return Currency::all()
            ->map(fn ($currency) => new CurrencyDTO(
                code: $currency->code,
                name: $currency->name,
                isDefault: $currency->is_default
            ))
            ->toArray();
    }

    /**
     * Retrieve all currencies with their latest exchange rates, excluding the default currency.
     *
     * This method returns a list of all currencies (excluding the default currency)
     * along with their most recent exchange rates relative to the default currency.
     * It ensures that each currency is returned with its code, name, whether it's
     * the default currency, and the latest rate.
     *
     * @return CurrencyWithLatestRateDTO[] An array of DTOs representing all currencies
     * with their latest rates.
     * @throws NoCurrencyException If no default currency is set.
     */
    public function getAllWithLatestRates(): array
    {
        $currencies = $this->getAll();
        $defaultCurrency = $this->findDefaultCurrency();

        if (!$defaultCurrency) {
            throw new NoCurrencyException('No default currency set');
        }

        // Filter out the default currency
        $currencies = array_filter($currencies, fn ($currency) => $currency->code !== $defaultCurrency->code);

        return array_map(fn ($currency) => new CurrencyWithLatestRateDTO(
            code: $currency->code,
            name: $currency->name,
            isDefault: $currency->isDefault,
            latestRate: $currency->code === $defaultCurrency->code
                ? 1.0
                : $this->getLatestRate($currency->code, $defaultCurrency->code)->rate
        ), $currencies);
    }


    /**
     * Save or update a currency.
     *
     * @param CurrencyDTO $currency The currency data to save.
     * @return void
     */
    public function save(CurrencyDTO $currency): void
    {

        if ($currency->isDefault) {
            // Reset default flag in all currencies
            Currency::where('is_default', true)->update(['is_default' => false]);
        }

        Currency::updateOrCreate(
            ['code' => $currency->code],
            [
                'name' => $currency->name,
                'is_default' => $currency->isDefault,
            ]
        );
    }

    /**
     * Set a currency as the default.
     *
     * @param string $code The currency code to set as default.
     * @return void
     */
    public function setDefaultCurrency(string $code): void
    {
        Currency::where('is_default', true)->update(['is_default' => false]);
        Currency::where('code', $code)->update(['is_default' => true]);
    }

    /**
     * Save a currency exchange rate.
     *
     * @param CurrencyRateDTO $rate The exchange rate data to save.
     * @return void
     */
    public function saveRate(CurrencyRateDTO $rate): void
    {
        CurrencyRate::create([
            'from_currency' => $rate->fromCurrency,
            'to_currency' => $rate->toCurrency,
            'rate' => $rate->rate,
            'created_at' => $rate->updatedAt
        ]);
    }

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
    public function getLatestRate(string $from, string $to): CurrencyRateDTO
    {
        // Firstly, we try to find direct rate
        $rate = CurrencyRate::where('from_currency', $from)
            ->where('to_currency', $to)
            ->latest('created_at')
            ->first();

        if ($rate) {
            return $this->mapRateToDTO($rate);
        }

        // Try to find inverse rate
        $inverseRate = CurrencyRate::where('from_currency', $to)
            ->where('to_currency', $from)
            ->latest('created_at')
            ->first();

        if ($inverseRate) {
            $actualRate = 1 / $inverseRate->rate;
            return $this->mapRateToDTO(
                $inverseRate,
                $from,
                $to,
                $actualRate
            );
        }

        // Try to find rate through default currency
        $defaultCurrency = $this->findDefaultCurrency();
        if (!$defaultCurrency) {
            throw new NoCurrencyException('No default currency set');
        }

        if ($from === $defaultCurrency->code || $to === $defaultCurrency->code) {
            throw new CurrencyRateNotFoundException(
                "Rate not found for pair {$from}-{$to}"
            );
        }

        $fromToDefault = $this->getLatestRate($from, $defaultCurrency->code);
        $defaultToTarget = $this->getLatestRate($defaultCurrency->code, $to);

        return new CurrencyRateDTO(
            fromCurrency: $from,
            toCurrency: $to,
            rate: $fromToDefault->rate * $defaultToTarget->rate,
            updatedAt: max($fromToDefault->updatedAt, $defaultToTarget->updatedAt)
        );
    }

    /**
     * Retrieve the rate history for a currency pair over a specific period.
     *
     * @param string $fromCurrency The source currency.
     * @param string $toCurrency The target currency.
     * @param DateTime $from The start date of the period.
     * @param DateTime $to The end date of the period.
     * @return CurrencyRateDTO[] An array of DTOs containing the rate history data.
     */
    public function getRateHistory(
        string $fromCurrency,
        string $toCurrency,
        DateTime $from,
        DateTime $to
    ): array {
        return CurrencyRate::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->map(fn (CurrencyRate $rate) => $this->mapRateToDTO($rate))
            ->toArray();
    }

    /**
     * Map CurrencyRate model to CurrencyRateDTO.
     *
     * @param CurrencyRate $rate The rate model to map
     * @param string|null $overrideFromCurrency Optional override for source currency
     * @param string|null $overrideToCurrency Optional override for target currency
     * @param float|null $overrideRate Optional override for rate value
     * @return CurrencyRateDTO
     * @throws CurrencyRateNotFoundException If rate timestamp is missing
     */
    private function mapRateToDTO(
        CurrencyRate $rate,
        ?string $overrideFromCurrency = null,
        ?string $overrideToCurrency = null,
        ?float $overrideRate = null
    ): CurrencyRateDTO {
        if (!$rate->created_at) {
            throw new CurrencyRateNotFoundException("Rate timestamp is missing");
        }

        return new CurrencyRateDTO(
            fromCurrency: $overrideFromCurrency ?? $rate->from_currency,
            toCurrency: $overrideToCurrency ?? $rate->to_currency,
            rate: $overrideRate ?? $rate->rate,
            updatedAt: DateTime::createFromInterface($rate->created_at)
        );
    }

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
    public function deleteCurrency(string $code): void
    {
        $currency = Currency::where('code', $code)->first();

        if (!$currency) {
            throw new CurrencyRateNotFoundException("Currency with code {$code} not found.");
        }

        // Delete the associated exchange rates
        CurrencyRate::where('from_currency', $code)
            ->orWhere('to_currency', $code)
            ->delete();

        $currency->delete();
    }
}

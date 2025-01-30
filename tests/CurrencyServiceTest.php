<?php

declare(strict_types=1);

namespace Tests;

use DateTime;
use Orchestra\Testbench\TestCase;
use rnr1721\CurrencyService\Contracts\CurrencyRepositoryInterface;
use rnr1721\CurrencyService\CurrencyServiceProvider;
use rnr1721\CurrencyService\Contracts\CurrencyServiceInterface;
use rnr1721\CurrencyService\DTO\ConversionSettingsDTO;
use rnr1721\CurrencyService\DTO\FormatSettingsDTO;
use rnr1721\CurrencyService\DTO\CurrencyDTO;
use rnr1721\CurrencyService\DTO\CurrencyRateDTO;
use rnr1721\CurrencyService\Exceptions\CurrencyNotFoundException;
use rnr1721\CurrencyService\Providers\TestRateProvider;

/**
 * @property \Illuminate\Foundation\Application $app
 */
class CurrencyServiceTest extends TestCase
{
    private CurrencyServiceInterface $service;
    private CurrencyRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../src/Database/Migrations');

        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $this->repository = $this->app->make(CurrencyRepositoryInterface::class);

        // Create base currencies for tests via repository
        $this->repository->save(new CurrencyDTO(
            code: 'USD',
            name: 'US Dollar',
            isDefault: true
        ));

        $this->repository->save(new CurrencyDTO(
            code: 'EUR',
            name: 'Euro',
            isDefault: false
        ));

        $this->repository->saveRate(new CurrencyRateDTO(
            fromCurrency: 'USD',
            toCurrency: 'EUR',
            rate: 0.92,
            updatedAt: new DateTime()
        ));
    }

    protected function getPackageProviders($app): array
    {
        return [
            CurrencyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('currency.providers.default', TestRateProvider::class);
        $app['config']->set('currency.cache_ttl', 0);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
            'serialize' => false,
        ]);
    }

    public function testConvertCurrency(): void
    {
        $amount = 100;
        $result = $this->service->convert($amount, 'USD', 'EUR');

        $this->assertEquals(92.0, $result);
    }

    public function testConvertCurrencyWithCustomSettings(): void
    {
        $amount = 100;
        $settings = new ConversionSettingsDTO(
            roundingPrecision: 3,
            roundingMode: PHP_ROUND_HALF_DOWN
        );

        $result = $this->service->convert($amount, 'USD', 'EUR', $settings);

        $this->assertEquals(92.000, $result);
    }

    public function testFormatCurrency(): void
    {
        $amount = 1234.56;
        $result = $this->service->format($amount, 'USD');

        $this->assertEquals('1,234.56 USD', $result);
    }

    public function testFormatCurrencyWithCustomSettings(): void
    {
        $amount = 1234.56;
        $settings = new FormatSettingsDTO(
            decimals: 1,
            decimalSeparator: ',',
            thousandsSeparator: ' ',
            showCurrencyCode: true,
            currencyPosition: 'before'
        );

        $result = $this->service->format($amount, 'USD', $settings);

        $this->assertEquals('USD 1 234,6', $result);
    }

    public function testGetDefaultCurrency(): void
    {
        $currency = $this->service->getDefaultCurrency();

        $this->assertEquals('USD', $currency->code);
        $this->assertTrue($currency->isDefault);
    }

    public function testSetDefaultCurrency(): void
    {
        $this->service->setDefaultCurrency('EUR');
        $currency = $this->service->getDefaultCurrency();

        $this->assertEquals('EUR', $currency->code);
        $this->assertTrue($currency->isDefault);
    }

    public function testSetDefaultCurrencyWithInvalidCode(): void
    {
        $this->expectException(CurrencyNotFoundException::class);

        $this->service->setDefaultCurrency('XXX');
    }

    public function testGetAllCurrencies(): void
    {
        $currencies = $this->service->getAllCurrencies();

        $this->assertCount(2, $currencies);
        $this->assertEquals('USD', $currencies[0]->code);
        $this->assertEquals('EUR', $currencies[1]->code);
    }
}

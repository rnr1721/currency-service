<?php

declare(strict_types=1);

namespace Tests;

use DateTime;
use Orchestra\Testbench\TestCase;
use rnr1721\CurrencyService\Contracts\CurrencyRateProviderInterface;
use rnr1721\CurrencyService\CurrencyServiceProvider;
use rnr1721\CurrencyService\Contracts\CurrencyRepositoryInterface;
use rnr1721\CurrencyService\Contracts\CurrencyServiceInterface;
use rnr1721\CurrencyService\DTO\CurrencyDTO;
use rnr1721\CurrencyService\Exceptions\CurrencyRateNotFoundException;
use rnr1721\CurrencyService\Providers\TestRateProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * @property \Illuminate\Foundation\Application $app
 */
class CurrencyServiceUpdateRatesTest extends TestCase
{
    private CurrencyServiceInterface $service;
    private CurrencyRepositoryInterface $repository;

    protected function getPackageProviders($app): array
    {
        return [
            CurrencyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Config for tests
        $app['config']->set('currency.providers.default', TestRateProvider::class);
        $app['config']->set('currency.cache_ttl', 0);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
            'serialize' => false,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../src/Database/Migrations');

        $this->service = $this->app->make(CurrencyServiceInterface::class);
        $this->repository = $this->app->make(CurrencyRepositoryInterface::class);

        // Only currencies without rates
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
    }

    public function testUpdateRates(): void
    {
        $this->service->updateRates();
        $rate = $this->service->getCurrencyRate('EUR', 'USD');
        $this->assertEquals(1.087, $rate->rate, 'Unexpected exchange rate');
    }

    public function testInverseRate(): void
    {
        $this->service->updateRates();
        $rate = $this->service->getCurrencyRate('USD', 'EUR');
        // USD to EUR must be inverse to EUR to USD (est 0.92)
        $this->assertEqualsWithDelta(1 / 1.087, $rate->rate, 0.001, 'Unexpected inverse rate');
    }

    public function testUpdateRatesRefreshesCache(): void
    {
        /** @var TestRateProvider $provider */
        $provider = $this->app->make(CurrencyRateProviderInterface::class);

        // First rates refresh and check
        $this->service->updateRates();
        sleep(1); // Delay before refresh created_at

        $initialRate = $this->service->getCurrencyRate('EUR', 'USD');
        $this->assertEquals(1.087, $initialRate->rate, 'Initial rate should be 1.087');

        // Change rate in provider
        $provider->reset();
        $provider->addRate('EUR', 1.176);
        $providerRates = $provider->getRates('USD');
        $this->assertEquals(1.176, $providerRates['EUR'], 'Provider should return new rate');

        $this->service->updateRates();

        // Clear cache forcing
        $this->app->make(CacheRepository::class)->flush();

        // Check using repository
        $newRate = $this->repository->getLatestRate('EUR', 'USD');
        $this->assertEquals(1.176, $newRate->rate, 'New rate should be saved in repository');

        // Check by service
        $serviceRate = $this->service->getCurrencyRate('EUR', 'USD');
        $this->assertEquals(1.176, $serviceRate->rate, 'Service should return new rate');
    }

    public function testRateTimestampUpdated(): void
    {
        $beforeUpdate = new DateTime();
        sleep(1); // Timestamp must be different

        $this->service->updateRates();
        $rate = $this->service->getCurrencyRate('EUR', 'USD');

        $this->assertGreaterThan($beforeUpdate, $rate->updatedAt, 'Rate timestamp should be updated');
    }

    public function testMultipleCurrencyRates(): void
    {
        // Add another one currency
        $this->repository->save(new CurrencyDTO(
            code: 'GBP',
            name: 'British Pound',
            isDefault: false
        ));

        $this->service->updateRates();

        // Check rates for all pairs
        $eurRate = $this->service->getCurrencyRate('EUR', 'USD');
        $gbpRate = $this->service->getCurrencyRate('GBP', 'USD');
        $eurGbpRate = $this->service->getCurrencyRate('EUR', 'GBP');

        $this->assertEquals(1.087, $eurRate->rate, 'EUR rate should be correct');
        $this->assertEquals(1.266, $gbpRate->rate, 'GBP rate should be correct');
        $this->assertEqualsWithDelta(
            1.087 / 1.266,
            $eurGbpRate->rate,
            0.001,
            'Cross rate should be calculated correctly'
        );
    }

    public function testSameRateCurrencyPair(): void
    {
        $this->service->updateRates();
        $rate = $this->service->getCurrencyRate('USD', 'USD');

        $this->assertEquals(1.0, $rate->rate, 'Same currency pair should have rate 1.0');
        $this->assertInstanceOf(DateTime::class, $rate->updatedAt);
    }

    public function testUnknownCurrencyRate(): void
    {
        $this->service->updateRates();

        $this->expectException(CurrencyRateNotFoundException::class);
        $this->service->getCurrencyRate('USD', 'XXX');
    }

    public function testRateHistoryAfterUpdate(): void
    {
        /** @var TestRateProvider $provider */
        $provider = $this->app->make(CurrencyRateProviderInterface::class);

        $this->service->updateRates();
        sleep(1);

        $provider->reset();
        $provider->addRate('EUR', 1.176);

        $this->service->updateRates();

        $history = $this->repository->getRateHistory(
            'EUR',
            'USD',
            (new DateTime())->modify('-1 minute'),
            new DateTime()
        );

        $this->assertCount(2, $history, 'Should have two historical rates');
        $this->assertEquals(1.087, $history[0]->rate, 'First historical rate should match');
        $this->assertEquals(1.176, $history[1]->rate, 'Second historical rate should match');
    }
}

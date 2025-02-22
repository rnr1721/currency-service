<?php

namespace rnr1721\CurrencyService;

use rnr1721\CurrencyService\Contracts\CurrencyRepositoryInterface;
use rnr1721\CurrencyService\Contracts\CurrencyRateProviderInterface;
use rnr1721\CurrencyService\Contracts\CurrencyServiceInterface;
use rnr1721\CurrencyService\Contracts\CurrencyRateHistoryRepositoryInterface;
use rnr1721\CurrencyService\Services\CurrencyService;
use rnr1721\CurrencyService\Services\CurrencyFormatterService;
use rnr1721\CurrencyService\Repositories\CurrencyRepository;
use rnr1721\CurrencyService\Repositories\CurrencyRateHistoryRepository;
use rnr1721\CurrencyService\Providers\OpenExchangeRatesProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class CurrencyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the configuration file
        $this->mergeConfigFrom(
            __DIR__ . '/Config/currency.php',
            'currency'
        );

        // Formatter registration
        // You can override the formatter by creating a new class and binding
        $this->app->singleton(CurrencyFormatterService::class, function ($app) {
            return new CurrencyFormatterService(
                $app['config']['currency']
            );
        });

        // Register the currency repository
        $this->app->singleton(CurrencyRepositoryInterface::class, function ($app) {
            return new CurrencyRepository();
        });

        // Repository registration and rate history
        $this->app->singleton(CurrencyRateHistoryRepositoryInterface::class, function ($app) {
            return new CurrencyRateHistoryRepository();
        });

        // Register the currency rate provider
        $this->app->singleton(CurrencyRateProviderInterface::class, function ($app) {
            $config = $app['config']['currency'];

            // Get the provider class from the config
            $providerClass = $config['providers']['default'];

            // If it is OpenExchangeRatesProvider
            if ($providerClass === OpenExchangeRatesProvider::class) {
                return new OpenExchangeRatesProvider(
                    $app->make('GuzzleHttp\Client'),
                    $config['providers']['openexchangerates']['api_key'] ?? ''
                );
            }

            // For other providers - using Laravel's service container
            // This allows constructor dependency injection for custom providers
            return $app->make($providerClass);
        });

        // Register the main service
        $this->app->singleton(CurrencyServiceInterface::class, function ($app) {
            $config = $app['config']['currency'];
            return new CurrencyService(
                $app->make(CurrencyRepositoryInterface::class),
                $app->make(CurrencyRateProviderInterface::class),
                $app->make(CacheRepository::class),
                $app->make(CurrencyFormatterService::class),
                $app['config']['currency'],
                (int) $config['cache_ttl']
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        if ($this->app->runningInConsole()) {
            // Config publication
            $this->publishes([
                __DIR__ . '/Config/currency.php' => config_path('currency.php'),
            ], 'currency-config');

            // Migration publication
            $this->publishes([
                __DIR__ . '/Database/Migrations' => database_path('migrations'),
            ], 'currency-migrations');
        }
    }
}

# Laravel Currency Service

Looking for a powerful and easy-to-use currency management tool for your Laravel application? Our Laravel Currency Service offers everything you need to manage currencies, handle conversions, track exchange rates, and store historical data — all in one seamless package.

## Features

- Currency conversion with configurable precision and rounding modes
- Customizable currency formatting (position, separators, decimals)
- Exchange rate management with historical tracking
- Multiple rate providers support (includes TestRateProvider and OpenExchangeRates)
- Advanced caching system
- Historical rates analysis
- SOLID architecture principles
- Comprehensive test coverage
- Full Laravel integration

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Composer

## Installation

```bash
composer require rnr1721/currency-service

# Publish configuration file
php artisan vendor:publish --tag=currency-config

# Run migrations (migrations will run directly from the package)
php artisan migrate

# Optionally, if you want to customize migrations:
php artisan vendor:publish --tag=currency-migrations

```

## Configuration (If you want to make automatic rates synchronization etc)

The configuration file will be published to Config/currency.php:

```php
return [
    'cache_ttl' => 3600,
    'providers' => [
        'default' => OpenExchangeRatesProvider::class, // You can write own if you need rates sync
    ],
    'formatting' => [
        'decimals' => 2,
        'decimal_separator' => '.',
        'thousands_separator' => ',',
        'show_currency_code' => true,
        'currency_position' => 'after',
    ],
    'conversion' => [
        'rounding_precision' => 2,
        'rounding_mode' => PHP_ROUND_HALF_UP,
    ]
];
```

## Currency Management

### Initial Setup

1. First, create your base currencies. At minimum, you should define a default currency:

```php
use rnr1721\CurrencyService\Facades\Currency;
use rnr1721\CurrencyService\DTO\CurrencyDTO;
use rnr1721\CurrencyService\Repositories\CurrencyRepository;

// Get repository instance
$repository = app(CurrencyRepository::class);

// Create default currency
$repository->save(new CurrencyDTO(
    code: 'USD',
    name: 'United States Dollar',
    isDefault: true
));

// Add more currencies
$repository->save(new CurrencyDTO(
    code: 'EUR',
    name: 'Euro',
    isDefault: false
));

$repository->save(new CurrencyDTO(
    code: 'GBP',
    name: 'British Pound',
    isDefault: false
));
```

### Creating or Updating Currencies

The save() method handles both creation and updates based on currency code:

```php
// This will create a new currency if JPY doesn't exist
// or update it if it already exists
$repository->save(new CurrencyDTO(
    code: 'JPY',
    name: 'Japanese Yen',
    isDefault: false
));
```

### Retrieving Currencies

```php
// Get all currencies
$currencies = Currency::getAllCurrencies();

// Get all currencies with actual rates to default currency (except default currency)
$currencies = Currency::getAllCurrenciesWithRates();

// Get specific currency
$usd = $repository->findByCode('USD');

// Get default currency
$default = Currency::getDefaultCurrency();
```

Or, available DI way

```php
// You can inject CurrencyServiceInterface in standard Laravel way
use rnr1721\CurrencyService\Contracts\CurrencyServiceInterface;

$currencies = $currencyService->getAllCurrencies();
$currencyRates = $currencyService->getAllCurrenciesWithRates();

```

### Manual Rate Management

If you're not using an automatic rate provider, you can manually manage rates:

```php
// You can inject CurrencyServiceInterface in standard Laravel way
use rnr1721\CurrencyService\Contracts\CurrencyServiceInterface;

// Will set USD rate to default currency as 42.5
$service->saveDefaultRate('USD', 42.5);
```

Or more custom way:

```php
// You can inject CurrencyRepositoryInterface in standard Laravel way
use rnr1721\CurrencyService\Contracts\CurrencyRepositoryInterface;
use rnr1721\CurrencyService\DTO\CurrencyRateDTO;

// Set rate from USD to EUR
$repository->saveRate(new CurrencyRateDTO(
    fromCurrency: 'USD',
    toCurrency: 'EUR',
    rate: 0.92,
    updatedAt: new DateTime()
));

// Rates are automatically inversed when needed
// so you don't need to save both USD->EUR and EUR->USD
```

## Deleting the currencies

```php
// You can inject CurrencyRepositoryInterface in standard Laravel way
use rnr1721\CurrencyService\Contracts\CurrencyRepositoryInterface;

// Will try to delete EUR currency with related rate history
$repository->deleteCurrency('EUR');
```

## Currency Administration Tips

### Initial Setup

- Always set up a default currency first
- Use standardized 3-letter ISO currency codes
- Consider implementing a seeder for basic currencies


### Rate Management

- Update rates regularly if managing manually
- Consider implementing a schedule for rate updates
- Keep historical rates for reporting


### Best Practices

- Validate currency codes before saving
- Handle rate update failures gracefully
- Consider implementing rate validity periods
- Add logging for important currency operations

## Basic Usage

```php
use rnr1721\CurrencyService\Facades\Currency;

// Currency conversion
$euros = Currency::convert(100, 'USD', 'EUR');

// Convert 100 USD to default currency
$amount = Currency::convert(100, 'USD');

// Format amount
$formatted = Currency::format(99.99, 'USD'); // "99.99 USD"

// Format amount with default currency
$formatted = Currency::format(99.99);

// Get current exchange rate
$rate = Currency::getCurrencyRate('USD', 'EUR');

// Get current exchange rate (to default currenct)
$rate = Currency::getCurrencyRate('USD');

// Set default currency
Currency::setDefaultCurrency('EUR');

// Update exchange rates
Currency::updateRates();

// Get all currencies
Currency::getAllCurrencies();

// Get current default currency
Currency::getDefaultCurrency();

```

of if you prefer Di, you can inject it and use:

```php
namespace App\Http\Controllers;

use rnr1721\CurrencyService\Contracts\CurrencyServiceInterface;

class CurrencyController extends Controller
{
    private CurrencyServiceInterface $currencyService;

    public function __construct(CurrencyServiceInterface $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function convert()
    {
        $amount = 100;
        $fromCurrency = 'USD';
        $toCurrency = 'EUR';

        $convertedAmount = $this->currencyService->convert($amount, $fromCurrency, $toCurrency);

        return response()->json([
            'converted_amount' => $convertedAmount
        ]);
    }
}
```

## Advanced Usage

```php
use rnr1721\CurrencyService\DTO\FormatSettingsDTO;
use rnr1721\CurrencyService\DTO\ConversionSettingsDTO;

// Custom formatting
use rnr1721\CurrencyService\DTO\FormatSettingsDTO;

$formatted = Currency::format(
    amount: 99.99,
    currencyCode: 'USD',
    settings: new FormatSettingsDTO(
        decimals: 2,
        decimalSeparator: ',',
        thousandsSeparator: ' ',
        showCurrencyCode: true,
        currencyPosition: 'before'
    )
); // "USD 99,99"

// Custom conversion settings
$converted = Currency::convert(
    amount: 100,
    from: 'USD',
    to: 'EUR',
    settings: new ConversionSettingsDTO(
        roundingPrecision: 3,
        roundingMode: PHP_ROUND_HALF_DOWN
    )
);
```

### Working with Exchange Rate History

```php
use rnr1721\CurrencyService\Facades\Currency;
use DateTime;

// Get paginated history
$history = app(CurrencyRateHistoryRepositoryInterface::class)->getPaginatedHistory(
    fromCurrency: 'USD',
    toCurrency: 'EUR',
    startDate: new DateTime('-30 days'),
    perPage: 20
);

// Get statistics
$stats = app(CurrencyRateHistoryRepositoryInterface::class)->getHistoryStats(
    fromCurrency: 'USD',
    toCurrency: 'EUR',
    startDate: new DateTime('-7 days'),
    endDate: new DateTime()
);
```

### Custom Rate Providers

You can create your own rate providers by implementing CurrencyRateProviderInterface:

```php
use rnr1721\CurrencyService\Contracts\CurrencyRateProviderInterface;

class CustomRateProvider implements CurrencyRateProviderInterface
{
    public function getRates(string $baseCurrency): array
    {
        // Implement your rate fetching logic
        return [
            'EUR' => 0.92,
            'GBP' => 0.79,
            // ...
        ];
    }
}
```

Then register it in your configuration:

```php
'providers' => [
    'default' => CustomRateProvider::class,
],
```

## Exception Handling

The service throws the following exceptions:

- CurrencyNotFoundException - When a requested currency doesn't exist
- CurrencyRateNotFoundException - When an exchange rate is not available
- CurrencyProviderException - When there's an error fetching rates
- NoCurrencyException - When no default currency is set

## Testing

```bash
composer install
composer test
composer analyse
composer check-style
```

## License

MIT License. See the LICENSE file for details.

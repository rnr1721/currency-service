<?php

use rnr1721\CurrencyService\Providers\OpenExchangeRatesProvider;
use rnr1721\CurrencyService\Providers\TestRateProvider;

return [
    'cache_ttl' => 3600,
    'providers' => [
        'default' => TestRateProvider::class,
        'openexchangerates' => [
            'api_key' => env('OPENEXCHANGERATES_API_KEY', ''),
            'class' => OpenExchangeRatesProvider::class
        ],
    ],
    'formatting' => [
        'decimals' => 2,
        'decimal_separator' => '.',
        'thousands_separator' => ',',
        'show_currency_code' => true,
        // 'before' or 'after'
        'currency_position' => 'after',
    ],
    'conversion' => [
        // 0 - no rounding
        'rounding_precision' => 2,
        // PHP_ROUND_HALF_UP, PHP_ROUND_HALF_DOWN, PHP_ROUND_HALF_EVEN, PHP_ROUND_HALF_ODD
        'rounding_mode' => PHP_ROUND_HALF_UP,
    ]
];

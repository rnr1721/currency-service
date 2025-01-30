<?php

namespace rnr1721\CurrencyService\Facades;

use Illuminate\Support\Facades\Facade;
use rnr1721\CurrencyService\Contracts\CurrencyServiceInterface;

class Currency extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CurrencyServiceInterface::class;
    }
}

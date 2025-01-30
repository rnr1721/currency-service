<?php

namespace rnr1721\CurrencyService\Exceptions;

class CurrencyRateNotFoundException extends \Exception
{
    public function __construct(string $message = "Currency rate not found")
    {
        parent::__construct($message);
    }
}

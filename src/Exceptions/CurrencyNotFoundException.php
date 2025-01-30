<?php

namespace rnr1721\CurrencyService\Exceptions;

class CurrencyNotFoundException extends \Exception
{
    public function __construct(string $message = "Currency not found")
    {
        parent::__construct($message);
    }
}

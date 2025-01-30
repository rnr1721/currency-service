<?php

namespace rnr1721\CurrencyService\Exceptions;

class NoCurrencyException extends \Exception
{
    public function __construct(string $message = "No default currency set")
    {
        parent::__construct($message);
    }
}

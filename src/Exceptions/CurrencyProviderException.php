<?php

namespace rnr1721\CurrencyService\Exceptions;

use RuntimeException;

class CurrencyProviderException extends RuntimeException
{
    public function __construct(string $message = "Currency provider error", ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}

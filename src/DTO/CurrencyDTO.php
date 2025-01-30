<?php

namespace rnr1721\CurrencyService\DTO;

/**
 * Class CurrencyDTO
 * This class is a data transfer object for currency
 * @package rnr1721\CurrencyService\DTO
 */
class CurrencyDTO
{
    /**
     * CurrencyDTO constructor.
     * @param string $code Currency code
     * @param string $name Currency name
     * @param bool $isDefault Is this currency default
     */
    public function __construct(
        public string $code,
        public string $name,
        public bool $isDefault = false
    ) {
    }
}

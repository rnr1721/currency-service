<?php

namespace rnr1721\CurrencyService\DTO;

/**
 * Class CurrencyDTO
 * This class is a data transfer object for currency
 * @package rnr1721\CurrencyService\DTO
 */
class CurrencyDTO
{
    public string $code;
    public string $name;
    public bool $isDefault;

    /**
     * CurrencyDTO constructor.
     * @param string $code Currency code
     * @param string $name Currency name
     * @param bool $isDefault Is this currency default
     */
    public function __construct(
        string $code,
        string $name,
        bool $isDefault
    ) {
        if (strlen($code) !== 3) {
            throw new \InvalidArgumentException('Currency code must be 3 characters long');
        }
        if (empty($name)) {
            throw new \InvalidArgumentException('Currency name cannot be empty');
        }
        $this->code = strtoupper($code);
        $this->name = $name;
        $this->isDefault = $isDefault;
    }
}

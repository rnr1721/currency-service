<?php

namespace rnr1721\CurrencyService\DTO;

/**
 * Class ConversionSettingsDTO
 * This class is used to store settings for conversion
 * @package rnr1721\CurrencyService\DTO
 */
class ConversionSettingsDTO
{
    public const ROUND_HALF_UP = PHP_ROUND_HALF_UP;     // 1
    public const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN; // 2
    public const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN; // 3
    public const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;

    /**
     * ConversionSettingsDTO constructor.
     * @param int|null $roundingPrecision number of decimal places to round to
     * @param int $roundingMode rounding mode
     */
    public function __construct(
        public readonly ?int $roundingPrecision = null,
        /** @var 1|2|3|4 */
        public readonly int $roundingMode = self::ROUND_HALF_UP
    ) {
    }
}

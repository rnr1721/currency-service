<?php

namespace rnr1721\CurrencyService\DTO;

/**
 * DTO for rate statistics from database query
 */
class RateStatisticsDTO
{
    public function __construct(
        public readonly string $min_rate,
        public readonly string $max_rate,
        public readonly string $avg_rate,
        public readonly string $total_records
    ) {
    }

    /**
     * Create from database result
     *
     * @param mixed $data Raw database result
     * @return self
     */
    public static function fromDatabaseResult(mixed $data): self
    {
        return new self(
            min_rate: (string)$data->min_rate,
            max_rate: (string)$data->max_rate,
            avg_rate: (string)$data->avg_rate,
            total_records: (string)$data->total_records
        );
    }
}

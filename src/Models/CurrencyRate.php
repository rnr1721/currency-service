<?php

namespace rnr1721\CurrencyService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $from_currency
 * @property string $to_currency
 * @property float $rate
 * @property \Illuminate\Support\Carbon|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder where($column, $operator = null, $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder orWhere($column, $operator = null, $value = null)
 * @method static \Illuminate\Database\Eloquent\Model|null first()
 */
class CurrencyRate extends Model
{
    public $timestamps = false;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'created_at'
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'rate' => 'decimal:8',
        'created_at' => 'datetime'
    ];

    /**
     * Source currency
     * @return BelongsTo
     */
    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency', 'code');
    }

    /**
     * Target currency
     * @return BelongsTo
     */
    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency', 'code');
    }

    /**
     * Get inverse rate
     * @return float
     */
    public function getInverseRate(): float
    {
        return 1 / $this->rate;
    }
}

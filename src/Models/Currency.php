<?php

namespace rnr1721\CurrencyService\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $code
 * @property string $name
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder where($column, $operator = null, $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder orWhere($column, $operator = null, $value = null)
 */
class Currency extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'is_default'
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get rates where this currency is the source
     * @return HasMany
     */
    public function sourceRates(): HasMany
    {
        return $this->hasMany(CurrencyRate::class, 'from_currency', 'code');
    }

    /**
     * Get rates where this currency is the target
     * @return HasMany
     */
    public function targetRates(): HasMany
    {
        return $this->hasMany(CurrencyRate::class, 'to_currency', 'code');
    }

    /**
     * Get all rates related to this currency
     * (both source and target)
     *
     * @return Collection<int, CurrencyRate>
     */
    public function allRates(): Collection
    {
        return CurrencyRate::where('from_currency', $this->code)
            ->orWhere('to_currency', $this->code)
            ->get();
    }

    /**
     * Check if currency is default
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }
}

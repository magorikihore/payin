<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'buy_rate',
        'sell_rate',
        'conversion_fee_percent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'buy_rate' => 'decimal:6',
            'sell_rate' => 'decimal:6',
            'conversion_fee_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Find the active exchange rate for a currency pair.
     */
    public static function findRate(string $from, string $to): ?self
    {
        return static::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Calculate how much destination currency the user gets for a given source amount.
     * Returns: [to_amount, fee_amount, rate_applied, platform_revenue]
     */
    public function calculate(float $sourceAmount): array
    {
        $feePercent = (float) $this->conversion_fee_percent;
        $feeAmount = round($sourceAmount * ($feePercent / 100), 2);
        $netAmount = $sourceAmount - $feeAmount;

        // sell_rate = how much 1 unit of from_currency yields in to_currency
        $rate = (float) $this->sell_rate;
        $toAmount = round($netAmount * $rate, 2);

        return [
            'to_amount' => $toAmount,
            'fee_amount' => $feeAmount,
            'rate_applied' => $rate,
            'fee_percent' => $feePercent,
            'platform_revenue' => $feeAmount,
        ];
    }
}

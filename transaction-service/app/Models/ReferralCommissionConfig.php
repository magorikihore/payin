<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralCommissionConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'operator',
        'transaction_type',
        'commission_type',
        'commission_value',
        'tiers',
        'min_amount',
        'max_amount',
        'max_commission',
        'referrer_account_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'commission_value' => 'decimal:4',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'max_commission' => 'decimal:2',
            'tiers' => 'array',
        ];
    }

    /**
     * Calculate referral commission for a transaction.
     *
     * @param float  $amount           Transaction amount
     * @param string $operator         Operator code (e.g. 'airtelmoney')
     * @param string $transactionType  'collection' or 'disbursement'
     * @param int    $referrerAccountId The referrer's account ID
     * @return array {commission_amount, commission_type, commission_rate, config_id}
     */
    public static function calculateCommission(
        float $amount,
        string $operator,
        string $transactionType,
        int $referrerAccountId
    ): array {
        $configs = self::where('status', 'active')
            ->where(function ($q) use ($referrerAccountId) {
                $q->whereNull('referrer_account_id')
                  ->orWhere('referrer_account_id', $referrerAccountId);
            })
            ->where(function ($q) use ($operator) {
                $q->whereRaw('LOWER(operator) = ?', [strtolower($operator)])
                  ->orWhere('operator', 'all');
            })
            ->where(function ($q) use ($transactionType) {
                $q->where('transaction_type', $transactionType)
                  ->orWhere('transaction_type', 'all');
            })
            ->where(function ($q) use ($amount) {
                $q->where('min_amount', '<=', $amount)
                  ->where(function ($q2) use ($amount) {
                      $q2->where('max_amount', '>=', $amount)
                         ->orWhere('max_amount', 0);
                  });
            })
            ->get();

        // Referrer-specific configs override global ones
        $specific = $configs->whereNotNull('referrer_account_id');
        if ($specific->isNotEmpty()) {
            $configs = $specific;
        }

        if ($configs->isEmpty()) {
            return [
                'commission_amount' => 0,
                'commission_type' => null,
                'commission_rate' => 0,
                'config_id' => null,
            ];
        }

        // Use the first matching config (highest priority)
        $config = $configs->first();
        $commission = 0;
        $rate = (float) $config->commission_value;
        $type = $config->commission_type;

        if ($type === 'fixed') {
            $commission = $rate;
        } elseif ($type === 'percentage') {
            $commission = round($amount * $rate / 100, 2);
        } elseif ($type === 'dynamic' && is_array($config->tiers)) {
            foreach ($config->tiers as $tier) {
                $tierMin = (float) ($tier['min_amount'] ?? 0);
                $tierMax = (float) ($tier['max_amount'] ?? 0);
                if ($amount >= $tierMin && ($tierMax == 0 || $amount <= $tierMax)) {
                    $tierType = $tier['commission_type'] ?? 'fixed';
                    $tierValue = (float) ($tier['commission_value'] ?? 0);
                    $rate = $tierValue;
                    if ($tierType === 'percentage') {
                        $commission = round($amount * $tierValue / 100, 2);
                    } else {
                        $commission = $tierValue;
                    }
                    break;
                }
            }
        }

        // Apply max commission cap
        if ($config->max_commission > 0 && $commission > (float) $config->max_commission) {
            $commission = (float) $config->max_commission;
        }

        return [
            'commission_amount' => round($commission, 2),
            'commission_type' => $type,
            'commission_rate' => $rate,
            'config_id' => $config->id,
        ];
    }
}

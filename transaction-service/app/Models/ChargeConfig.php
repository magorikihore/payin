<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'operator',
        'transaction_type',
        'charge_type',
        'charge_value',
        'min_amount',
        'max_amount',
        'applies_to',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'charge_value' => 'decimal:4',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
        ];
    }

    /**
     * Calculate charge for a given amount, operator and transaction_type.
     */
    /**
     * Calculate charge for a given amount, operator, transaction_type, and optionally account_id.
     * Account-specific charges override global ones for the same applies_to+operator+type combo.
     */
    public static function calculateCharges(float $amount, string $operator, string $transactionType, $accountId = null): array
    {
        $platformCharge = 0;
        $operatorCharge = 0;

        // Build query: get configs that match this account OR are global (account_id IS NULL)
        $configs = self::where('status', 'active')
            ->where(function ($q) use ($accountId) {
                $q->whereNull('account_id');
                if ($accountId) {
                    $q->orWhere('account_id', $accountId);
                }
            })
            ->where(function ($q) use ($operator) {
                $q->where('operator', $operator)->orWhere('operator', 'all');
            })
            ->where(function ($q) use ($transactionType) {
                $q->where('transaction_type', $transactionType)->orWhere('transaction_type', 'all');
            })
            ->where(function ($q) use ($amount) {
                $q->where('min_amount', '<=', $amount)
                  ->where(function ($q2) use ($amount) {
                      $q2->where('max_amount', '>=', $amount)->orWhere('max_amount', 0);
                  });
            })
            ->get();

        // If there are account-specific configs, use ONLY those (they override global)
        if ($accountId) {
            $accountSpecific = $configs->whereNotNull('account_id');
            if ($accountSpecific->isNotEmpty()) {
                $configs = $accountSpecific;
            }
        }

        foreach ($configs as $config) {
            $chargeAmount = 0;
            if ($config->charge_type === 'fixed') {
                $chargeAmount = (float) $config->charge_value;
            } elseif ($config->charge_type === 'percentage') {
                $chargeAmount = round($amount * (float) $config->charge_value / 100, 2);
            }

            if ($config->applies_to === 'platform') {
                $platformCharge += $chargeAmount;
            } else {
                $operatorCharge += $chargeAmount;
            }
        }

        return [
            'platform_charge' => round($platformCharge, 2),
            'operator_charge' => round($operatorCharge, 2),
            'total_charge' => round($platformCharge + $operatorCharge, 2),
        ];
    }
}

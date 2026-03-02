<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyExchange extends Model
{
    protected $fillable = [
        'account_id',
        'user_id',
        'reference',
        'from_currency',
        'to_currency',
        'from_amount',
        'to_amount',
        'rate_applied',
        'fee_percent',
        'fee_amount',
        'platform_revenue',
        'from_operator',
        'to_operator',
        'from_wallet_type',
        'to_wallet_type',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'from_amount' => 'decimal:2',
            'to_amount' => 'decimal:2',
            'rate_applied' => 'decimal:6',
            'fee_percent' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'platform_revenue' => 'decimal:2',
            'metadata' => 'array',
        ];
    }
}

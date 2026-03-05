<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_account_id',
        'referred_account_id',
        'transaction_ref',
        'transaction_amount',
        'operator',
        'transaction_type',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'status',
        'wallet_reference',
    ];

    protected function casts(): array
    {
        return [
            'transaction_amount' => 'decimal:2',
            'commission_rate' => 'decimal:4',
            'commission_amount' => 'decimal:2',
        ];
    }
}

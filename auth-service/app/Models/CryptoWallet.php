<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryptoWallet extends Model
{
    protected $fillable = [
        'account_id',
        'currency',
        'network',
        'wallet_address',
        'label',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

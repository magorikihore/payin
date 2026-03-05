<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'transaction_ref',
        'amount',
        'platform_charge',
        'operator_charge',
        'currency',
        'type',
        'status',
        'description',
        'payment_method',
        'operator',
        'operator_receipt',
        'phone_number',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_charge' => 'decimal:2',
            'operator_charge' => 'decimal:2',
            'metadata' => 'array',
        ];
    }
}

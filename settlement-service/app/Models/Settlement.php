<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'settlement_ref',
        'amount',
        'currency',
        'operator',
        'status',
        'bank_name',
        'account_number',
        'account_name',
        'description',
        'settled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'settled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}

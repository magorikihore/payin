<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'amount',
        'currency',
        'bank_name',
        'account_number',
        'account_name',
        'branch',
        'swift_code',
        'description',
        'status',
        'requested_by',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reversal extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'account_id',
        'reversal_ref',
        'original_ref',
        'amount',
        'platform_charge',
        'operator_charge',
        'type',
        'operator',
        'reason',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_charge' => 'decimal:2',
            'operator_charge' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}

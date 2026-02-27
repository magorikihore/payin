<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternalTransfer extends Model
{
    protected $fillable = [
        'account_id',
        'operator',
        'amount',
        'reference',
        'description',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'admin_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];
}

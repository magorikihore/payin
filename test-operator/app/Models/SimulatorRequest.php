<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimulatorRequest extends Model
{
    protected $fillable = [
        'type',
        'command',
        'reference',
        'transaction_id',
        'msisdn',
        'amount',
        'currency',
        'callback_url',
        'sp_id',
        'merchant_code',
        'auth_valid',
        'gateway_id',
        'response_code',
        'callback_status',
        'callback_result',
        'receipt_number',
        'callback_sent_at',
        'callback_response',
        'raw_request',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'raw_request'      => 'array',
            'raw_response'     => 'array',
            'auth_valid'       => 'boolean',
            'amount'           => 'decimal:2',
            'callback_sent_at' => 'datetime',
        ];
    }
}

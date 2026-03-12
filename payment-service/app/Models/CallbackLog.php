<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallbackLog extends Model
{
    protected $fillable = [
        'operator_code',
        'format',
        'reference',
        'phone',
        'amount',
        'receipt_number',
        'status',
        'payment_request_id',
        'raw_payload',
        'parsed_data',
        'response_code',
        'ip_address',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'parsed_data' => 'array',
        'amount' => 'decimal:2',
    ];

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class);
    }
}

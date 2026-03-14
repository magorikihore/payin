<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'payment_request_id',
        'account_id',
        'url',
        'request_payload',
        'http_status',
        'response_body',
        'response_time_ms',
        'status',
        'error_message',
        'attempt_number',
    ];

    protected $casts = [
        'request_payload' => 'array',
    ];

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class);
    }
}

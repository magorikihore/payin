<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'request_ref',
        'external_ref',
        'operator_ref',
        'receipt_number',
        'gateway_id',
        'type',
        'phone',
        'amount',
        'platform_charge',
        'operator_charge',
        'currency',
        'operator_code',
        'operator_name',
        'status',
        'description',
        'batch_name',
        'operator_request',
        'operator_response',
        'callback_data',
        'error_message',
        'callback_status',
        'callback_attempts',
        'callback_sent_at',
        'transaction_id',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_charge' => 'decimal:2',
            'operator_charge' => 'decimal:2',
            'operator_request' => 'array',
            'operator_response' => 'array',
            'callback_data' => 'array',
            'callback_sent_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function operator()
    {
        return Operator::where('code', $this->operator_code)->first();
    }

    public function webhookLogs()
    {
        return $this->hasMany(WebhookLog::class);
    }
}

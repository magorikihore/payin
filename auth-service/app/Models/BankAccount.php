<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'account_id',
        'bank_name',
        'account_name',
        'account_number',
        'swift_code',
        'branch',
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

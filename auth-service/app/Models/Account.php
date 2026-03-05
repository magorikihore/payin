<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_ref',
        'business_name',
        'paybill',
        'callback_url',
        'business_type',
        'registration_number',
        'tin_number',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'currency',
        'multi_currency_enabled',
        'allowed_currencies',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_swift',
        'bank_branch',
        'crypto_wallet_address',
        'crypto_network',
        'crypto_currency',
        'id_type',
        'id_number',
        'id_document_url',
        'business_license_url',
        'certificate_of_incorporation_url',
        'tax_clearance_url',
        'tin_certificate_url',
        'company_memorandum_url',
        'company_resolution_url',
        'kyc_update_allowed',
        'kyc_notes',
        'kyc_submitted_at',
        'kyc_approved_at',
        'kyc_approved_by',
        'status',
        'rate_limit',
        'referral_code',
        'referred_by',
        'referred_at',
    ];

    protected $casts = [
        'kyc_submitted_at' => 'datetime',
        'kyc_approved_at' => 'datetime',
        'kyc_update_allowed' => 'boolean',
        'multi_currency_enabled' => 'boolean',
        'allowed_currencies' => 'array',
        'referred_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner()
    {
        return $this->hasOne(User::class)->where('role', 'owner');
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function cryptoWallets()
    {
        return $this->hasMany(CryptoWallet::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'api_url',
        'sp_id',
        'merchant_code',
        'sp_password',
        'api_version',
        'collection_path',
        'disbursement_path',
        'status_path',
        'callback_url',
        'status',
        'extra_config',
    ];

    protected $hidden = [
        'sp_password',
    ];

    protected function casts(): array
    {
        return [
            'extra_config' => 'array',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Generate spPassword for operator API header.
     * Formula: base64(sha256(spId + spPassword + timestamp))
     */
    public function generateSpPassword(string $timestamp): string
    {
        $raw = $this->sp_id . $this->sp_password . $timestamp;
        return base64_encode(hash('sha256', $raw, true));
    }

    /**
     * Build the operator API header.
     */
    public function buildApiHeader(): array
    {
        $timestamp = now()->format('YmdHis');
        return [
            'spId'         => $this->sp_id,
            'merchantCode' => $this->merchant_code,
            'spPassword'   => $this->generateSpPassword($timestamp),
            'timestamp'    => $timestamp,
            'apiVersion'   => $this->api_version ?? '5.0',
        ];
    }
}

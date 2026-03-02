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
        'prefixes',
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
        'gateway_type',
        'country',
        'country_code',
        'currency',
    ];

    protected $hidden = [
        'sp_password',
    ];

    protected function casts(): array
    {
        return [
            'extra_config' => 'array',
            'prefixes'     => 'array',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Detect the operator by phone number prefix.
     * Supports multiple countries by checking the country_code column.
     * Phone can be in format: local (0XX), international (+CCCXX), or subscriber (9 digits).
     * Returns the matching active Operator or null.
     */
    public static function detectByPhone(string $phone): ?self
    {
        // Normalize: strip spaces, dashes, dots, leading +
        $phone = preg_replace('/[\s\-\.]/', '', $phone);
        $phone = ltrim($phone, '+');

        $operators = self::where('status', 'active')
            ->whereNotNull('prefixes')
            ->get();

        foreach ($operators as $operator) {
            $prefixes = $operator->prefixes ?? [];
            $cc = $operator->country_code ?? '255';

            // Try to match with country code prefix
            if (str_starts_with($phone, $cc)) {
                $local = '0' . substr($phone, strlen($cc));
                $prefix = substr($local, 0, 3);
                if (in_array($prefix, $prefixes)) {
                    return $operator;
                }
            }

            // Try local format (starts with 0)
            if (str_starts_with($phone, '0')) {
                $prefix = substr($phone, 0, 3);
                if (in_array($prefix, $prefixes)) {
                    return $operator;
                }
            }

            // Try subscriber number (9 digits, no leading 0 or country code)
            if (!str_starts_with($phone, '0') && strlen($phone) === 9) {
                $prefix = '0' . substr($phone, 0, 2);
                if (in_array($prefix, $prefixes)) {
                    return $operator;
                }
            }
        }

        return null;
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

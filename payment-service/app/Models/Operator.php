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
     * Phone can be in format: XXXXXXXXX (9 digits), 0XXXXXXXXX (10 digits),
     * 255XXXXXXXXX (12 digits), or +255XXXXXXXXX.
     * Prefixes stored as 3-digit local format: 075, 065, 068, 062, etc.
     * Returns the matching active Operator or null.
     */
    public static function detectByPhone(string $phone): ?self
    {
        // Normalize: strip spaces, dashes, dots, leading +
        $phone = preg_replace('/[\s\-\.]/', '', $phone);
        $phone = ltrim($phone, '+');

        // Convert to local 10-digit format (0XXXXXXXXX)
        if (str_starts_with($phone, '255') && strlen($phone) >= 12) {
            // 255XXXXXXXXX -> 0XXXXXXXXX
            $phone = '0' . substr($phone, 3);
        } elseif (!str_starts_with($phone, '0') && strlen($phone) === 9) {
            // 9-digit subscriber number -> 0XXXXXXXXX
            $phone = '0' . $phone;
        }

        // Must be at least 10 digits and start with 0
        if (!str_starts_with($phone, '0') || strlen($phone) < 10) {
            return null;
        }

        // Extract the 3-digit prefix (e.g., 0754xxx -> "075")
        $prefix = substr($phone, 0, 3);

        $operators = self::where('status', 'active')
            ->whereNotNull('prefixes')
            ->get();

        foreach ($operators as $operator) {
            $prefixes = $operator->prefixes ?? [];
            if (in_array($prefix, $prefixes)) {
                return $operator;
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

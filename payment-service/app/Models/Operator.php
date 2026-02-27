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
     * Phone can be in format: 0XXXXXXXXX, 255XXXXXXXXX, or +255XXXXXXXXX
     * Returns the matching active Operator or null.
     */
    public static function detectByPhone(string $phone): ?self
    {
        // Normalize: strip spaces, dashes, dots, leading +
        $phone = preg_replace('/[\s\-\.]/', '', $phone);
        $phone = ltrim($phone, '+');

        // Convert 0xx to 255xx
        if (str_starts_with($phone, '0')) {
            $phone = '255' . substr($phone, 1);
        }

        // Must start with 255 and have at least 12 digits
        if (!str_starts_with($phone, '255') || strlen($phone) < 12) {
            return null;
        }

        // Extract the 2-digit prefix after 255 (e.g., 255 74 xxx -> "74")
        $prefix = substr($phone, 3, 2);

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

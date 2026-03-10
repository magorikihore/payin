<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'action',
        'description',
        'ip_address',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * Log an activity.
     */
    public static function record(
        string $action,
        string $description,
        ?int $userId = null,
        ?int $accountId = null,
        ?string $ip = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'user_id' => $userId,
            'account_id' => $accountId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log from a request context (authenticated user).
     */
    public static function log(Request $request, string $action, string $description, ?array $metadata = null): self
    {
        $user = $request->user();

        return static::record(
            action: $action,
            description: $description,
            userId: $user?->id,
            accountId: $user?->account_id,
            ip: $request->ip(),
            metadata: $metadata,
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

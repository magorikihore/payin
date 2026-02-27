<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AccountRateLimit
{
    /**
     * Enforce per-account rate limiting based on the account's rate_limit setting.
     * Uses a sliding window counter stored in cache.
     *
     * The rate_limit is requests per minute (set by admin in auth-service).
     * Account data is injected by ApiKeyAuthenticate or AuthServiceAuthenticate middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $accountId = $user->account_id ?? ($user->account['id'] ?? null);

        if (!$accountId) {
            return $next($request);
        }

        // Get rate limit from account data (injected by auth middleware)
        $account = $user->account ?? null;
        $maxAttempts = 60; // default

        if (is_array($account) && isset($account['rate_limit'])) {
            $maxAttempts = (int) $account['rate_limit'];
        } elseif (is_object($account) && isset($account->rate_limit)) {
            $maxAttempts = (int) $account->rate_limit;
        }

        $key = "rate_limit:account:{$accountId}";
        $decayMinutes = 1;

        // Get current hit count
        $hits = Cache::get($key, 0);

        if ($hits >= $maxAttempts) {
            $retryAfter = Cache::get("{$key}:timer", 60);

            return response()->json([
                'message' => 'Too many requests. Please slow down.',
                'rate_limit' => $maxAttempts,
                'retry_after' => $retryAfter,
            ], 429, [
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter,
            ]);
        }

        // Increment counter
        if ($hits === 0) {
            Cache::put($key, 1, now()->addMinutes($decayMinutes));
            Cache::put("{$key}:timer", 60, now()->addMinutes($decayMinutes));
        } else {
            Cache::increment($key);
        }

        $response = $next($request);

        // Add rate limit headers to response
        $remaining = max(0, $maxAttempts - ($hits + 1));
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);

        return $response;
    }
}

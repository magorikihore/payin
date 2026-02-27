<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthenticate
{
    /**
     * Authenticate using API key + secret.
     * Merchant sends: X-API-Key and X-API-Secret headers.
     * We validate against auth-service and inject account data.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $apiSecret = $request->header('X-API-Secret');

        if (!$apiKey || !$apiSecret) {
            return response()->json([
                'message' => 'API key authentication required. Provide X-API-Key and X-API-Secret headers.',
            ], 401);
        }

        try {
            $authServiceUrl = config('services.auth_service.url');
            $response = Http::timeout(10)->post("{$authServiceUrl}/api/internal/validate-api-key", [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ]);

            if ($response->failed()) {
                $data = $response->json();
                return response()->json([
                    'message' => $data['message'] ?? 'Invalid API credentials.',
                ], 401);
            }

            $data = $response->json();

            if (!($data['valid'] ?? false)) {
                return response()->json(['message' => 'Invalid API credentials.'], 401);
            }

            // Check account status
            $account = $data['account'] ?? null;
            if ($account && ($account['status'] ?? '') !== 'active') {
                return response()->json(['message' => 'Account is not active.'], 403);
            }

            // Inject user and account data into request
            $userData = $data['user'] ?? [];
            $userData['account'] = $account;
            $request->merge(['auth_user' => $userData]);
            $request->setUserResolver(fn () => (object) $userData);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Authentication service unavailable.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 503);
        }

        return $next($request);
    }
}

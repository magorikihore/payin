<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class AuthServiceAuthenticate
{
    /**
     * Handle an incoming request by validating the Bearer token
     * against the auth-service's /api/user endpoint.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'message' => 'Unauthenticated. Token not provided.',
            ], 401);
        }

        try {
            $authServiceUrl = config('services.auth_service.url');

            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$authServiceUrl}/api/user");

            if ($response->failed()) {
                return response()->json([
                    'message' => 'Unauthenticated. Invalid or expired token.',
                ], 401);
            }

            // Attach the authenticated user data to the request
            $userData = $response->json();
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

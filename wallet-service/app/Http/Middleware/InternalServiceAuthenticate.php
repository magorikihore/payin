<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalServiceAuthenticate
{
    /**
     * Validate internal service-to-service requests using a shared service key.
     * The key is sent via the X-Service-Key header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $serviceKey = $request->header('X-Service-Key');
        $expectedKey = config('services.internal_service_key');

        if (!$serviceKey || !$expectedKey || $serviceKey !== $expectedKey) {
            return response()->json([
                'message' => 'Unauthorized. Invalid service key.',
            ], 401);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ValidateInternalRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip validation for zip upload endpoint
        if ($request->is('api/products/upload-zip') && $request->method() === 'POST') {
            Log::info('Skipping internal request validation for zip upload endpoint');
            return $next($request);
        }

        // Log request details for security monitoring
        $requestId = $request->header('X-Request-ID');
        $userId = $request->header('X-User-Id');

        Log::info('Catalog service request', [
            'request_id' => $requestId,
            'user_id' => $userId,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        // 1. Reject any request missing X-Client-App or X-Request-ID
        if (!$request->hasHeader('X-Client-App') || !$request->hasHeader('X-Request-ID')) {
            Log::warning('Missing required client headers', [
                'request_id' => $requestId,
                'has_client_app' => $request->hasHeader('X-Client-App'),
                'has_request_id' => $request->hasHeader('X-Request-ID'),
                'server_vars' => [
                    'HTTP_X_CLIENT_APP' => $_SERVER['HTTP_X_CLIENT_APP'] ?? 'not set',
                    'HTTP_X_REQUEST_ID' => $_SERVER['HTTP_X_REQUEST_ID'] ?? 'not set',
                ],
                'all_headers' => $request->headers->all(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Missing required client headers'
            ], 403);
        }

        // 2. Accept requests ONLY if X-Client-App == "grok-web"
        $clientApp = $request->header('X-Client-App');
        if ($clientApp !== 'grok-web') {
            Log::warning('Invalid client app', [
                'request_id' => $requestId,
                'client_app' => $clientApp,
            ]);

            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Invalid client app'
            ], 403);
        }

        // 3. Ignore Authorization header completely - we don't parse or validate JWTs
        // This middleware does not check Authorization header

        // 4. Replay protection - if X-Timestamp exists, reject requests older than 60 seconds
        if ($request->hasHeader('X-Timestamp')) {
            $timestamp = $request->header('X-Timestamp');
            $currentTime = time();
            $requestTime = (int) $timestamp;

            if (($currentTime - $requestTime) > 60) {
                Log::warning('Replay attack detected - request too old', [
                    'request_id' => $requestId,
                    'timestamp' => $timestamp,
                    'current_time' => $currentTime,
                    'age_seconds' => $currentTime - $requestTime,
                ]);

                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'Request timestamp too old'
                ], 403);
            }
        }

        return $next($request);
    }
}

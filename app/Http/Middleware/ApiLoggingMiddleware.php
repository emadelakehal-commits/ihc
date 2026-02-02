<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log incoming request to dedicated API log channel
        \Log::channel('api')->info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => [
                'accept' => $request->header('accept'),
                'content-type' => $request->header('content-type'),
                'authorization' => $request->hasHeader('authorization') ? 'present' : 'absent',
            ],
            'params' => $this->sanitizeRequestData($request),
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

        // Log response to dedicated API log channel
        \Log::channel('api')->info('API Response', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'response_size' => strlen($response->getContent()),
        ]);

        return $response;
    }

    /**
     * Sanitize sensitive data from request logging
     */
    private function sanitizeRequestData(Request $request): array
    {
        $data = $request->all();

        // Remove sensitive fields
        $sensitiveFields = ['password', 'token', 'api_key', 'secret', 'authorization'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        // Limit array sizes for logging
        foreach ($data as $key => $value) {
            if (is_array($value) && count($value) > 10) {
                $data[$key] = array_slice($value, 0, 10);
                $data[$key]['...'] = (count($value) - 10) . ' more items';
            }
        }

        return $data;
    }
}
